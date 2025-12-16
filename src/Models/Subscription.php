<?php

namespace Mhmadahmd\Filasaas\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mhmadahmd\Filasaas\Traits\BelongsToPlan;
use Mhmadahmd\Filasaas\Traits\HasSlug;
use Mhmadahmd\Filasaas\Traits\HasTranslations;

class Subscription extends Model
{
    use BelongsToPlan;
    use HasFactory;
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'filasaas_subscriptions';

    protected $fillable = [
        'subscriber_type',
        'subscriber_id',
        'plan_id',
        'slug',
        'name',
        'description',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancels_at',
        'canceled_at',
        'stripe_id',
        'stripe_status',
        'paddle_id',
        'paypal_subscription_id',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancels_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    public function usage(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class, 'subscription_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id');
    }

    public function latestPayment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class, 'id', 'subscription_id')
            ->latestOfMany();
    }

    public function active(): bool
    {
        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    public function inactive(): bool
    {
        return ! $this->active();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function canceled(): bool
    {
        return $this->canceled_at !== null;
    }

    public function ended(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function cancel(bool $immediately = false): self
    {
        $this->canceled_at = now();

        if ($immediately) {
            $this->ends_at = now();
        } else {
            $this->cancels_at = $this->ends_at;
        }

        $this->save();

        return $this;
    }

    public function changePlan(Plan $newPlan, array $options = []): self
    {
        $this->plan_id = $newPlan->id;
        $this->save();

        return $this;
    }

    public function renew(): self
    {
        if ($this->plan) {
            $period = new \Mhmadahmd\Filasaas\Services\Period(
                $this->plan->invoice_interval,
                $this->plan->invoice_period,
                $this->ends_at ?? now()
            );

            $this->starts_at = $period->getStartDate();
            $this->ends_at = $period->getEndDate();
            $this->save();
        }

        return $this;
    }

    public function recordFeatureUsage(int $featureId, int $uses = 1): SubscriptionUsage
    {
        $usage = $this->usage()->firstOrNew(['feature_id' => $featureId]);
        $usage->used += $uses;

        $feature = Feature::find($featureId);
        if ($feature && $feature->getResetDate()) {
            $usage->valid_until = $feature->getResetDate();
        }

        $usage->save();

        return $usage;
    }

    public function reduceFeatureUsage(int $featureId, int $uses = 1): bool
    {
        $usage = $this->usage()->where('feature_id', $featureId)->first();

        if (! $usage) {
            return false;
        }

        $usage->used = max(0, $usage->used - $uses);
        $usage->save();

        return true;
    }

    public function canUseFeature(int $featureId): bool
    {
        $feature = Feature::find($featureId);

        if (! $feature) {
            return false;
        }

        if ($feature->isUnlimited()) {
            return true;
        }

        if ($feature->isBoolean()) {
            return $feature->value === 'true';
        }

        $usage = $this->getFeatureUsage($featureId);
        $limit = (int) $feature->value;

        return $usage < $limit;
    }

    public function getFeatureUsage(int $featureId): int
    {
        $usage = $this->usage()->where('feature_id', $featureId)->first();

        return $usage ? $usage->used : 0;
    }

    public function getFeatureRemainings(int $featureId): ?int
    {
        $feature = Feature::find($featureId);

        if (! $feature || $feature->isUnlimited()) {
            return null;
        }

        if ($feature->isBoolean()) {
            return $feature->value === 'true' ? 1 : 0;
        }

        $limit = (int) $feature->value;
        $used = $this->getFeatureUsage($featureId);

        return max(0, $limit - $used);
    }

    // Scopes
    public function scopeOfSubscriber($query, $subscriber)
    {
        return $query->where('subscriber_type', get_class($subscriber))
            ->where('subscriber_id', $subscriber->id);
    }

    public function scopeFindActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('ends_at')
                ->orWhere('ends_at', '>', now());
        })->whereNull('canceled_at');
    }

    public function scopeFindEndingTrial($query, int $days = 3)
    {
        return $query->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeFindEndedTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now());
    }

    public function scopeFindEndingPeriod($query, int $days = 3)
    {
        return $query->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeFindEndedPeriod($query)
    {
        return $query->whereNotNull('ends_at')
            ->where('ends_at', '<=', now());
    }
}
