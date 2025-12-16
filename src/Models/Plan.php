<?php

namespace Mhmadahmd\Filasaas\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mhmadahmd\Filasaas\Traits\HasSlug;
use Mhmadahmd\Filasaas\Traits\HasTranslations;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Plan extends Model implements Sortable
{
    use HasFactory;
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;
    use SortableTrait;

    protected $table = 'filasaas_plans';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'price',
        'signup_fee',
        'currency',
        'trial_period',
        'trial_interval',
        'invoice_period',
        'invoice_interval',
        'grace_period',
        'grace_interval',
        'prorate_day',
        'prorate_period',
        'prorate_extend_due',
        'active_subscribers_limit',
        'sort_order',
        'cash_auto_approve',
        'allowed_payment_gateways',
        'stripe_price_id',
        'paddle_price_id',
        'paypal_plan_id',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'signup_fee' => 'decimal:2',
        'cash_auto_approve' => 'boolean',
        'allowed_payment_gateways' => 'array',
        'prorate_extend_due' => 'boolean',
    ];

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function isFree(): bool
    {
        return $this->price == 0;
    }

    public function hasTrial(): bool
    {
        return $this->trial_period > 0;
    }

    public function hasGrace(): bool
    {
        return $this->grace_period > 0;
    }

    public function isPaidInFull(): bool
    {
        return $this->invoice_period == 0 || $this->invoice_interval === null;
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function getFeatureBySlug(string $slug): ?Feature
    {
        return $this->features()->where('slug', $slug)->first();
    }

    public function getAllowedGateways(): array
    {
        $gateways = $this->allowed_payment_gateways;
        
        // If null or empty array, return default gateways
        if (empty($gateways)) {
            return ['cash', 'stripe', 'paypal'];
        }
        
        return $gateways;
    }
}
