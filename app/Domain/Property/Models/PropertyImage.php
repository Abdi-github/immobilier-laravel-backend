<?php

declare(strict_types=1);

namespace App\Domain\Property\Models;

use App\Domain\Property\Enums\ImageSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyImage extends Model
{
    protected $fillable = [
        'property_id',
        'public_id',
        'version',
        'signature',
        'url',
        'secure_url',
        'thumbnail_url',
        'thumbnail_secure_url',
        'width',
        'height',
        'format',
        'bytes',
        'resource_type',
        'alt_text',
        'caption',
        'sort_order',
        'is_primary',
        'source',
        'original_filename',
        'external_url',
        'original_url',
        'migrated_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'bytes' => 'integer',
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
            'source' => ImageSource::class,
            'migrated_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // ── Scopes ──

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
