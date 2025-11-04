<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'property_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public static function booted(): void
    {
        static::creating(function (Favorite $favorite) {
            $favorite->created_at = $favorite->freshTimestamp();
        });
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Property\Models\Property::class);
    }
}
