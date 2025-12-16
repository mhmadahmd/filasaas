<?php

namespace Mhmadahmd\Filasaas\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mhmadahmd\Filasaas\Services\PaymentGatewayManager;

class SubscriptionPayment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'filasaas_subscription_payments';

    // Payment Methods
    public const METHOD_CASH = 'cash';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_ONLINE = 'online';

    // Payment Status
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    // Gateways
    public const GATEWAY_CASH = 'cash';

    public const GATEWAY_STRIPE = 'stripe';

    public const GATEWAY_PAYPAL = 'paypal';

    public const GATEWAY_CUSTOM = 'custom_local';

    protected $fillable = [
        'subscription_id',
        'gateway',
        'payment_method',
        'amount',
        'currency',
        'status',
        'gateway_transaction_id',
        'gateway_response',
        'transaction_id',
        'reference_number',
        'notes',
        'requires_approval',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function approver(): BelongsTo
    {
        $billableModel = \Illuminate\Support\Facades\Config::get('filasaas.billable_model', \App\Models\User::class);

        return $this->belongsTo($billableModel, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function markAsPaid(?Carbon $paidAt = null): self
    {
        $this->status = self::STATUS_PAID;
        $this->paid_at = $paidAt ?? now();
        $this->save();

        return $this;
    }

    public function markAsFailed(): self
    {
        $this->status = self::STATUS_FAILED;
        $this->save();

        return $this;
    }

    public function markAsRefunded(): self
    {
        $this->status = self::STATUS_REFUNDED;
        $this->save();

        return $this;
    }

    public function approve($approver): self
    {
        $this->requires_approval = false;
        $this->approved_by = is_object($approver) ? $approver->id : $approver;
        $this->approved_at = now();
        $this->markAsPaid();

        return $this;
    }

    public function getGatewayAttribute(): ?\Mhmadahmd\Filasaas\Contracts\PaymentGatewayInterface
    {
        $manager = \Illuminate\Support\Facades\App::make(PaymentGatewayManager::class);

        return $manager->get($this->attributes['gateway'] ?? null);
    }

    // Scopes
    public function scopePendingApproval($query)
    {
        return $query->where('requires_approval', true)
            ->where('status', self::STATUS_PENDING);
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }
}
