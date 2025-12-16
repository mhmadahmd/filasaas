<?php

namespace Mhmadahmd\Filasaas\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mhmadahmd\Filasaas\Traits\HasTranslations;

class Feature extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $table = 'filasaas_plan_features';

    protected $fillable = [
        'plan_id',
        'slug',
        'name',
        'description',
        'value',
        'resettable_period',
        'resettable_interval',
        'sort_order',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function getResetDate(): ?Carbon
    {
        if (! $this->resettable_period || ! $this->resettable_interval) {
            return null;
        }

        return match ($this->resettable_interval) {
            'day' => now()->addDays($this->resettable_period),
            'week' => now()->addWeeks($this->resettable_period),
            'month' => now()->addMonths($this->resettable_period),
            'year' => now()->addYears($this->resettable_period),
            default => null,
        };
    }

    public function isUnlimited(): bool
    {
        return $this->value === 'unlimited';
    }

    public function isBoolean(): bool
    {
        return in_array($this->value, ['true', 'false']);
    }
}
