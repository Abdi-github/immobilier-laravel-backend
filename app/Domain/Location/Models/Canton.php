<?php

declare(strict_types=1);

namespace App\Domain\Location\Models;

use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Canton extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected static function newFactory(): \Database\Factories\CantonFactory
    {
        return \Database\Factories\CantonFactory::new();
    }

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function agencies(): HasMany
    {
        return $this->hasMany(\App\Domain\Agency\Models\Agency::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(\App\Domain\Property\Models\Property::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
