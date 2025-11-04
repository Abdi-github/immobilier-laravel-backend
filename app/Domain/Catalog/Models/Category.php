<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\CategorySection;
use App\Domain\Shared\Traits\HasSlug;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasSlug, HasTranslations;

    public array $translatable = ['name'];

    protected static function newFactory(): \Database\Factories\CategoryFactory
    {
        return \Database\Factories\CategoryFactory::new();
    }

    protected $fillable = [
        'section',
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'section' => CategorySection::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function properties(): HasMany
    {
        return $this->hasMany(\App\Domain\Property\Models\Property::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySection($query, CategorySection|string $section)
    {
        $value = $section instanceof CategorySection ? $section->value : $section;

        return $query->where('section', $value);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ── Slug ──

    public function getSlugSource(): string
    {
        $name = $this->name;

        return is_array($name) ? ($name['en'] ?? reset($name)) : (string) $name;
    }
}
