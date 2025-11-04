<?php

declare(strict_types=1);

namespace App\Domain\Property\Models;

use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Property extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function newFactory(): \Database\Factories\PropertyFactory
    {
        return \Database\Factories\PropertyFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'price', 'owner_id', 'agency_id', 'reviewed_by', 'rejection_reason'])
            ->logOnlyDirty()
            ->useLogName('property');
    }

    protected $fillable = [
        'external_id',
        'external_url',
        'source_language',
        'category_id',
        'agency_id',
        'owner_id',
        'transaction_type',
        'price',
        'currency',
        'additional_costs',
        'rooms',
        'surface',
        'address',
        'city_id',
        'canton_id',
        'postal_code',
        'proximity',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => TransactionType::class,
            'status' => PropertyStatus::class,
            'price' => 'decimal:2',
            'additional_costs' => 'decimal:2',
            'rooms' => 'decimal:1',
            'surface' => 'decimal:2',
            'proximity' => 'array',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Catalog\Models\Category::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Agency\Models\Agency::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'owner_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Location\Models\City::class);
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Location\Models\Canton::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'reviewed_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PropertyTranslation::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domain\Catalog\Models\Amenity::class,
            'property_amenity'
        );
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(\App\Domain\User\Models\Favorite::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(\App\Domain\Lead\Models\Lead::class);
    }

    // ── Scopes ──

    public function scopePublished($query)
    {
        return $query->where('status', PropertyStatus::PUBLISHED);
    }

    public function scopeByStatus($query, PropertyStatus|string $status)
    {
        $value = $status instanceof PropertyStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopeByTransactionType($query, TransactionType|string $type)
    {
        $value = $type instanceof TransactionType ? $type->value : $type;

        return $query->where('transaction_type', $value);
    }

    public function scopeByCanton($query, int $cantonId)
    {
        return $query->where('canton_id', $cantonId);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopePriceRange($query, ?float $min = null, ?float $max = null)
    {
        return $query
            ->when($min, fn ($q) => $q->where('price', '>=', $min))
            ->when($max, fn ($q) => $q->where('price', '<=', $max));
    }

    // ── Helpers ──

    public function isPublished(): bool
    {
        return $this->status === PropertyStatus::PUBLISHED;
    }

    public function getTranslation(string $language): ?PropertyTranslation
    {
        return $this->translations->firstWhere('language', $language);
    }
}
