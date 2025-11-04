<?php

declare(strict_types=1);

namespace App\Domain\Location\Models;

use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected static function newFactory(): \Database\Factories\CityFactory
    {
        return \Database\Factories\CityFactory::new();
    }

    protected $fillable = [
        'canton_id',
        'name',
        'postal_code',
        'image_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
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

    public function scopeByPostalCode($query, string $postalCode)
    {
        return $query->where('postal_code', $postalCode);
    }

    public function scopeByCanton($query, int $cantonId)
    {
        return $query->where('canton_id', $cantonId);
    }
}
