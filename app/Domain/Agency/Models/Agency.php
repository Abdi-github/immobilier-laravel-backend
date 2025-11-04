<?php

declare(strict_types=1);

namespace App\Domain\Agency\Models;

use App\Domain\Shared\Traits\HasSlug;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Agency extends Model
{
    use HasFactory, HasSlug, HasTranslations, LogsActivity, SoftDeletes;

    public array $translatable = ['description'];

    protected static function newFactory(): \Database\Factories\AgencyFactory
    {
        return \Database\Factories\AgencyFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'is_verified'])
            ->logOnlyDirty()
            ->useLogName('agency');
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'website',
        'email',
        'phone',
        'contact_person',
        'address',
        'city_id',
        'canton_id',
        'postal_code',
        'status',
        'is_verified',
        'verification_date',
        'total_properties',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verification_date' => 'datetime',
            'total_properties' => 'integer',
        ];
    }

    // ── Relationships ──

    public function city(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Location\Models\City::class);
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Location\Models\Canton::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(\App\Domain\User\Models\User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(\App\Domain\Property\Models\Property::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(\App\Domain\Lead\Models\Lead::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByCanton($query, int $cantonId)
    {
        return $query->where('canton_id', $cantonId);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    // ── Slug ──

    public function getSlugSource(): string
    {
        return $this->name;
    }
}
