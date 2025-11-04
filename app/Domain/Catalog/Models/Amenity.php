<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\AmenityGroup;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'group',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'group' => AmenityGroup::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domain\Property\Models\Property::class,
            'property_amenity'
        );
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByGroup($query, AmenityGroup|string $group)
    {
        $value = $group instanceof AmenityGroup ? $group->value : $group;

        return $query->where('group', $value);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
