<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Models\Property;
use App\Domain\Property\Repositories\PropertyRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentPropertyRepository extends BaseRepository implements PropertyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Property::class);
    }

    public function findByExternalId(string $externalId): ?Property
    {
        return Property::where('external_id', $externalId)
            ->with(['category', 'canton', 'city', 'agency', 'primaryImage', 'images', 'amenities', 'translations'])
            ->first();
    }

    public function findPublished(array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildPublishedQuery($filters);

        return $query->paginate(
            $filters['limit'] ?? 20,
            ['*'],
            'page',
            $filters['page'] ?? 1,
        );
    }

    public function findPublishedWithCursor(array $filters = []): CursorPaginator
    {
        $query = $this->buildPublishedQuery($filters);

        return $query->cursorPaginate($filters['limit'] ?? 20);
    }

    public function findByCanton(int $cantonId, array $filters = []): LengthAwarePaginator
    {
        return $this->findPublished(array_merge($filters, ['canton_id' => $cantonId]));
    }

    public function findByCity(int $cityId, array $filters = []): LengthAwarePaginator
    {
        return $this->findPublished(array_merge($filters, ['city_id' => $cityId]));
    }

    public function findByAgency(int $agencyId, array $filters = []): LengthAwarePaginator
    {
        return Property::query()
            ->where('agency_id', $agencyId)
            ->where('status', PropertyStatus::PUBLISHED)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->with(['category', 'canton', 'city', 'primaryImage', 'translations'])
            ->orderByDesc('created_at')
            ->paginate($filters['limit'] ?? 20, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function findByCategory(int $categoryId, array $filters = []): LengthAwarePaginator
    {
        return $this->findPublished(array_merge($filters, ['category_id' => $categoryId]));
    }

    public function getStatistics(): array
    {
        $query = Property::query();

        return [
            'total' => $query->count(),
            'published' => $query->clone()->where('status', PropertyStatus::PUBLISHED)->count(),
            'pending' => $query->clone()->where('status', PropertyStatus::PENDING_APPROVAL)->count(),
            'draft' => $query->clone()->where('status', PropertyStatus::DRAFT)->count(),
            'archived' => $query->clone()->where('status', PropertyStatus::ARCHIVED)->count(),
        ];
    }

    public function updateStatus(int $id, PropertyStatus $status, ?int $reviewerId = null): Property
    {
        $property = $this->findByIdOrFail($id);

        $data = ['status' => $status];

        if ($reviewerId) {
            $data['reviewed_by'] = $reviewerId;
            $data['reviewed_at'] = now();
        }

        if ($status === PropertyStatus::PUBLISHED) {
            $data['published_at'] = now();
        }

        if ($status === PropertyStatus::DRAFT) {
            $data['reviewed_by'] = null;
            $data['reviewed_at'] = null;
            $data['rejection_reason'] = null;
        }

        $property->update($data);

        return $property->fresh();
    }

    public function findAll(array $filters = []): LengthAwarePaginator
    {
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['order'] ?? 'desc';

        $allowedSorts = ['created_at', 'updated_at', 'published_at', 'price', 'rooms', 'surface'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }

        return Property::query()
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['canton_id'] ?? null, fn ($q, $id) => $q->where('canton_id', $id))
            ->when($filters['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->when($filters['postal_code'] ?? null, fn ($q, $pc) => $q->where('postal_code', $pc))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['agency_id'] ?? null, fn ($q, $id) => $q->where('agency_id', $id))
            ->when($filters['owner_id'] ?? null, fn ($q, $id) => $q->where('owner_id', $id))
            ->when($filters['transaction_type'] ?? null, fn ($q, $t) => $q->where('transaction_type', $t))
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
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('address', 'ILIKE', "%{$search}%")
                        ->orWhere('external_id', 'ILIKE', "%{$search}%")
                        ->orWhereHas('translations', fn ($tq) => $tq->where('title', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%"));
                });
            })
            ->with(['category', 'canton', 'city', 'agency', 'primaryImage', 'translations'])
            ->orderBy($sortField, $sortDirection)
            ->orderByDesc('id')
            ->paginate($filters['limit'] ?? 20, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function getScopedStatistics(?int $agencyId = null, ?int $ownerId = null): array
    {
        $query = Property::query()
            ->when($agencyId, fn ($q, $id) => $q->where('agency_id', $id))
            ->when($ownerId, fn ($q, $id) => $q->where('owner_id', $id));

        $total = $query->count();

        $byStatus = [];
        foreach (PropertyStatus::cases() as $status) {
            $byStatus[$status->value] = $query->clone()->where('status', $status)->count();
        }

        $byTransactionType = [
            'rent' => $query->clone()->where('transaction_type', 'rent')->count(),
            'buy' => $query->clone()->where('transaction_type', 'buy')->count(),
        ];

        $avgBuy = $query->clone()->where('transaction_type', 'buy')->avg('price');
        $avgRent = $query->clone()->where('transaction_type', 'rent')->avg('price');

        $byCanton = Property::query()
            ->when($agencyId, fn ($q, $id) => $q->where('properties.agency_id', $id))
            ->when($ownerId, fn ($q, $id) => $q->where('properties.owner_id', $id))
            ->join('cantons', 'properties.canton_id', '=', 'cantons.id')
            ->selectRaw('cantons.id as canton_id, cantons.name::text as canton_name, cantons.code as canton_code, count(*) as count')
            ->groupBy('cantons.id', 'cantons.code')
            ->orderByDesc('count')
            ->get()
            ->map(function ($row) {
                $name = json_decode($row->canton_name, true);
                $row->canton_name = $name;
                return $row;
            })
            ->toArray();

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_transaction_type' => $byTransactionType,
            'by_canton' => $byCanton,
            'average_price' => [
                'buy' => $avgBuy ? round((float) $avgBuy, 2) : null,
                'rent' => $avgRent ? round((float) $avgRent, 2) : null,
            ],
        ];
    }

    public function findWithRelations(int $id): ?Property
    {
        return Property::with([
            'category', 'canton', 'city', 'agency', 'owner',
            'reviewer', 'primaryImage', 'images', 'amenities', 'translations',
        ])->find($id);
    }

    public function syncAmenities(int $id, array $amenityIds): void
    {
        $property = $this->findByIdOrFail($id);
        $property->amenities()->sync($amenityIds);
    }

    private function buildPublishedQuery(array $filters = []): Builder
    {
        $sortField = $filters['sort'] ?? 'published_at';
        $sortDirection = $filters['order'] ?? 'desc';

        $allowedSorts = ['created_at', 'updated_at', 'published_at', 'price', 'rooms', 'surface'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'published_at';
        }

        return Property::query()
            ->where('status', PropertyStatus::PUBLISHED)
            ->when($filters['canton_id'] ?? null, fn ($q, $id) => $q->where('canton_id', $id))
            ->when($filters['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->when($filters['postal_code'] ?? null, fn ($q, $pc) => $q->where('postal_code', $pc))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['agency_id'] ?? null, fn ($q, $id) => $q->where('agency_id', $id))
            ->when($filters['transaction_type'] ?? null, fn ($q, $t) => $q->where('transaction_type', $t))
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
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('address', 'ILIKE', "%{$search}%")
                        ->orWhere('external_id', 'ILIKE', "%{$search}%")
                        ->orWhereHas('translations', fn ($tq) => $tq->where('title', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%"));
                });
            })
            ->with(['category', 'canton', 'city', 'primaryImage', 'translations'])
            ->orderBy($sortField, $sortDirection)
            ->orderByDesc('id');
    }
}
