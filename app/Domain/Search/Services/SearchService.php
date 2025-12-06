<?php

declare(strict_types=1);

namespace App\Domain\Search\Services;

use App\Domain\Location\Models\Canton;
use App\Domain\Location\Models\City;
use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Models\Property;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class SearchService
{
    public function searchProperties(array $filters = []): LengthAwarePaginator
    {
        return $this->buildSearchQuery($filters)
            ->paginate($filters['limit'] ?? 20, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function searchPropertiesWithCursor(array $filters = []): CursorPaginator
    {
        return $this->buildSearchQuery($filters)
            ->cursorPaginate($filters['limit'] ?? 20);
    }

    public function searchLocations(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 10;
        $results = [];

        if ($options['include_cantons'] ?? true) {
            $results['cantons'] = Canton::query()
                ->active()
                ->where(function ($q) use ($query) {
                    $q->whereRaw("name::text ILIKE ?", ["%{$query}%"])
                        ->orWhere('code', 'ILIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get();
        }

        if ($options['include_cities'] ?? true) {
            $results['cities'] = City::query()
                ->active()
                ->where(function ($q) use ($query) {
                    $q->whereRaw("name::text ILIKE ?", ["%{$query}%"])
                        ->orWhere('postal_code', 'LIKE', "%{$query}%");
                })
                ->with('canton')
                ->limit($limit)
                ->get();
        }

        return $results;
    }

    public function unifiedSearch(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 5;

        $properties = Property::query()
            ->where('status', PropertyStatus::PUBLISHED)
            ->where(function ($q) use ($query) {
                $q->where('address', 'ILIKE', "%{$query}%")
                    ->orWhereHas('translations', fn ($tq) => $tq->where('title', 'ILIKE', "%{$query}%"));
            })
            ->with(['category', 'canton', 'city', 'primaryImage', 'translations'])
            ->limit($limit)
            ->get();

        $locations = $this->searchLocations($query, ['limit' => $limit]);

        return [
            'properties' => $properties,
            'cantons' => $locations['cantons'] ?? collect(),
            'cities' => $locations['cities'] ?? collect(),
        ];
    }

    public function getSuggestions(string $query, int $limit = 5): array
    {
        $suggestions = collect();

        // Property title suggestions
        $titleMatches = Property::query()
            ->where('status', PropertyStatus::PUBLISHED)
            ->whereHas('translations', fn ($q) => $q->where('title', 'ILIKE', "%{$query}%"))
            ->with(['translations'])
            ->limit($limit)
            ->get()
            ->map(function ($property) {
                $locale = app()->getLocale();
                $translation = $property->translations->firstWhere('language', $locale)
                    ?? $property->translations->first();

                return [
                    'type' => 'property',
                    'text' => $translation?->title,
                    'id' => (string) $property->id,
                ];
            });

        $suggestions = $suggestions->merge($titleMatches);

        // Location suggestions
        $cities = City::query()
            ->active()
            ->whereRaw("name::text ILIKE ?", ["%{$query}%"])
            ->with('canton')
            ->limit($limit)
            ->get()
            ->map(fn ($city) => [
                'type' => 'city',
                'text' => $city->getTranslation('name'),
                'id' => (string) $city->id,
                'postal_code' => $city->postal_code,
            ]);

        $suggestions = $suggestions->merge($cities);

        return $suggestions->take($limit)->values()->toArray();
    }

    public function getFacets(array $filters = []): array
    {
        $baseQuery = Property::query()
            ->where('status', PropertyStatus::PUBLISHED)
            ->when($filters['canton_id'] ?? null, fn ($q, $id) => $q->where('canton_id', $id))
            ->when($filters['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['transaction_type'] ?? null, fn ($q, $t) => $q->where('transaction_type', $t))
            ->when($filters['price_min'] ?? null, fn ($q, $min) => $q->where('price', '>=', $min))
            ->when($filters['price_max'] ?? null, fn ($q, $max) => $q->where('price', '<=', $max));

        $fields = $filters['fields'] ?? 'category_id,canton_id,transaction_type';
        $fieldList = is_string($fields) ? explode(',', $fields) : $fields;

        $facets = [];

        foreach ($fieldList as $field) {
            $field = trim($field);

            if (in_array($field, ['category_id', 'canton_id', 'transaction_type', 'city_id'], true)) {
                $facets[$field] = $baseQuery->clone()
                    ->selectRaw("{$field} as _id, COUNT(*) as count")
                    ->groupBy($field)
                    ->orderByDesc('count')
                    ->get()
                    ->toArray();
            }
        }

        return $facets;
    }

    private function buildSearchQuery(array $filters = []): Builder
    {
        $sortField = $filters['sort'] ?? 'published_at';
        $sortDirection = $filters['order'] ?? 'desc';

        $allowedSorts = ['created_at', 'updated_at', 'published_at', 'price', 'rooms', 'surface'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'published_at';
        }

        return Property::query()
            ->where('status', PropertyStatus::PUBLISHED)
            ->when($filters['q'] ?? null, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('address', 'ILIKE', "%{$search}%")
                        ->orWhere('postal_code', 'LIKE', "%{$search}%")
                        ->orWhereHas('translations', fn ($tq) => $tq->where('title', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%"));
                });
            })
            ->when($filters['canton_id'] ?? null, fn ($q, $id) => $q->where('canton_id', $id))
            ->when($filters['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->when($filters['postal_code'] ?? null, fn ($q, $pc) => $q->where('postal_code', $pc))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['transaction_type'] ?? null, fn ($q, $t) => $q->where('transaction_type', $t))
            ->when($filters['agency_id'] ?? null, fn ($q, $id) => $q->where('agency_id', $id))
            ->when($filters['price_min'] ?? null, fn ($q, $min) => $q->where('price', '>=', $min))
            ->when($filters['price_max'] ?? null, fn ($q, $max) => $q->where('price', '<=', $max))
            ->when($filters['rooms_min'] ?? null, fn ($q, $min) => $q->where('rooms', '>=', $min))
            ->when($filters['rooms_max'] ?? null, fn ($q, $max) => $q->where('rooms', '<=', $max))
            ->when($filters['surface_min'] ?? null, fn ($q, $min) => $q->where('surface', '>=', $min))
            ->when($filters['surface_max'] ?? null, fn ($q, $max) => $q->where('surface', '<=', $max))
            ->when($filters['amenities'] ?? null, function ($q, $amenities) {
                $amenityIds = is_string($amenities) ? explode(',', $amenities) : $amenities;
                $q->whereHas('amenities', fn ($aq) => $aq->whereIn('amenities.id', $amenityIds));
            })
            ->with(['category', 'canton', 'city', 'primaryImage', 'translations'])
            ->orderBy($sortField, $sortDirection)
            ->orderByDesc('id');
    }
}
