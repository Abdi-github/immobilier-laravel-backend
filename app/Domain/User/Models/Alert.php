<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\User\Enums\AlertFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'criteria',
        'frequency',
        'is_active',
        'last_sent_at',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'frequency' => AlertFrequency::class,
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
