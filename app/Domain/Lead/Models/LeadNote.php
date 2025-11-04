<?php

declare(strict_types=1);

namespace App\Domain\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadNote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'content',
        'is_internal',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public static function booted(): void
    {
        static::creating(function (LeadNote $note) {
            $note->created_at = $note->freshTimestamp();
        });
    }

    // ── Relationships ──

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'created_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->creator();
    }
}
