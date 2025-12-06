<?php

declare(strict_types=1);

namespace App\Domain\Property\Repositories;

use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Models\Property;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertyRepositoryInterface extends RepositoryInterface
{
    public function findByExternalId(string $externalId): ?Property;

    public function findPublished(array $filters = []): LengthAwarePaginator;

    public function findPublishedWithCursor(array $filters = []): CursorPaginator;

    public function findByCanton(int $cantonId, array $filters = []): LengthAwarePaginator;

    public function findByCity(int $cityId, array $filters = []): LengthAwarePaginator;

    public function findByAgency(int $agencyId, array $filters = []): LengthAwarePaginator;

    public function findByCategory(int $categoryId, array $filters = []): LengthAwarePaginator;

    public function getStatistics(): array;

    public function updateStatus(int $id, PropertyStatus $status, ?int $reviewerId = null): Property;

    /**
     * Admin: find all properties (any status) with filters and user-type scoping.
     */
    public function findAll(array $filters = []): LengthAwarePaginator;

    /**
     * Admin: get statistics scoped to user.
     */
    public function getScopedStatistics(?int $agencyId = null, ?int $ownerId = null): array;

    /**
     * Find a property with all relationships loaded for admin detail view.
     */
    public function findWithRelations(int $id): ?Property;

    /**
     * Sync amenities for a property.
     */
    public function syncAmenities(int $id, array $amenityIds): void;
}
