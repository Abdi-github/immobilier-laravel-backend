<?php

declare(strict_types=1);

namespace App\Domain\Property\Models;

use App\Domain\Property\Enums\TranslationSource;
use App\Domain\Translation\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyTranslation extends Model
{
    protected $fillable = [
        'property_id',
        'language',
        'title',
        'description',
        'source',
        'quality_score',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'source' => TranslationSource::class,
            'quality_score' => 'integer',
            'approval_status' => ApprovalStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'approved_by');
    }

    // ── Scopes ──

    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', ApprovalStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', ApprovalStatus::APPROVED);
    }
}
