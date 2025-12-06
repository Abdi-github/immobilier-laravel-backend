<?php

declare(strict_types=1);

namespace App\Domain\Property\Services;

use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Events\PropertyApproved;
use App\Domain\Property\Events\PropertyArchived;
use App\Domain\Property\Events\PropertyCreated;
use App\Domain\Property\Events\PropertyPublished;
use App\Domain\Property\Events\PropertyRejected;
use App\Domain\Property\Events\PropertySubmitted;
use App\Domain\Property\Models\Property;
use App\Domain\Property\Models\PropertyImage;
use App\Domain\Property\Repositories\PropertyImageRepositoryInterface;
use App\Domain\Property\Repositories\PropertyRepositoryInterface;
use App\Domain\Shared\Exceptions\DomainException;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class PropertyService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyImageRepositoryInterface $propertyImageRepository,
    ) {}

    // ── Public read methods ──

    public function getPublishedProperties(array $filters = []): LengthAwarePaginator
    {
        return $this->propertyRepository->findPublished($filters);
    }

    public function getPublishedPropertiesWithCursor(array $filters = []): CursorPaginator
    {
        return $this->propertyRepository->findPublishedWithCursor($filters);
    }

    public function getPropertyById(int $id): ?Property
    {
        return $this->propertyRepository->findWithRelations($id);
    }

    public function getPublishedPropertyById(int $id): ?Property
    {
        $property = $this->getPropertyById($id);

        if (! $property || ! $property->isPublished()) {
            return null;
        }

        return $property;
    }

    public function getPropertyByExternalId(string $externalId): ?Property
    {
        return $this->propertyRepository->findByExternalId($externalId);
    }

    public function getPropertiesByCanton(int $cantonId, array $filters = []): LengthAwarePaginator
    {
        return $this->propertyRepository->findByCanton($cantonId, $filters);
    }

    public function getPropertiesByCity(int $cityId, array $filters = []): LengthAwarePaginator
    {
        return $this->propertyRepository->findByCity($cityId, $filters);
    }

    public function getPropertiesByAgency(int $agencyId, array $filters = []): LengthAwarePaginator
    {
        return $this->propertyRepository->findByAgency($agencyId, $filters);
    }

    public function getPropertiesByCategory(int $categoryId, array $filters = []): LengthAwarePaginator
    {
        return $this->propertyRepository->findByCategory($categoryId, $filters);
    }

    public function getPropertyImages(int $propertyId): Collection
    {
        return $this->propertyImageRepository->findByProperty($propertyId);
    }

    // ── Admin / Agent CRUD ──

    public function getAllProperties(array $filters = []): LengthAwarePaginator
    {
        return $this->propertyRepository->findAll($filters);
    }

    public function getStatistics(?int $agencyId = null, ?int $ownerId = null): array
    {
        return $this->propertyRepository->getScopedStatistics($agencyId, $ownerId);
    }

    public function createProperty(array $data): Property
    {
        if (empty($data['external_id'])) {
            $data['external_id'] = 'PROP-' . strtoupper(Str::random(8));
        }

        $data['status'] = $data['status'] ?? PropertyStatus::DRAFT->value;

        $amenities = $data['amenities'] ?? null;
        unset($data['amenities']);

        $property = $this->propertyRepository->create($data);

        if ($amenities) {
            $this->propertyRepository->syncAmenities($property->id, $amenities);
        }

        $property = $this->propertyRepository->findWithRelations($property->id);

        PropertyCreated::dispatch($property);

        return $property;
    }

    public function updateProperty(int $id, array $data): Property
    {
        $amenities = $data['amenities'] ?? null;
        unset($data['amenities']);

        $property = $this->propertyRepository->update($id, $data);

        if ($amenities !== null) {
            $this->propertyRepository->syncAmenities($id, $amenities);
        }

        return $this->propertyRepository->findWithRelations($id);
    }

    public function deleteProperty(int $id): bool
    {
        return $this->propertyRepository->delete($id);
    }

    // ── Status Workflow ──

    public function submitForApproval(int $id): Property
    {
        $property = $this->propertyRepository->findByIdOrFail($id);
        $this->validateTransition($property, PropertyStatus::PENDING_APPROVAL);

        $property = $this->propertyRepository->updateStatus($id, PropertyStatus::PENDING_APPROVAL);
        PropertySubmitted::dispatch($property);

        return $this->propertyRepository->findWithRelations($id);
    }

    public function approve(int $id, int $reviewerId): Property
    {
        $property = $this->propertyRepository->findByIdOrFail($id);
        $this->validateTransition($property, PropertyStatus::APPROVED);

        $property = $this->propertyRepository->updateStatus($id, PropertyStatus::APPROVED, $reviewerId);
        PropertyApproved::dispatch($property, $reviewerId);

        return $this->propertyRepository->findWithRelations($id);
    }

    public function reject(int $id, int $reviewerId, string $reason): Property
    {
        $property = $this->propertyRepository->findByIdOrFail($id);
        $this->validateTransition($property, PropertyStatus::REJECTED);

        $property->update(['rejection_reason' => $reason]);
        $property = $this->propertyRepository->updateStatus($id, PropertyStatus::REJECTED, $reviewerId);
        PropertyRejected::dispatch($property, $reviewerId, $reason);

        return $this->propertyRepository->findWithRelations($id);
    }

    public function publish(int $id): Property
    {
        $property = $this->propertyRepository->findByIdOrFail($id);
        $this->validateTransition($property, PropertyStatus::PUBLISHED);

        $property = $this->propertyRepository->updateStatus($id, PropertyStatus::PUBLISHED);
        PropertyPublished::dispatch($property);

        return $this->propertyRepository->findWithRelations($id);
    }

    public function archive(int $id): Property
    {
        $property = $this->propertyRepository->findByIdOrFail($id);

        if ($property->status === PropertyStatus::ARCHIVED) {
            throw new DomainException('Property is already archived.', 422);
        }

        $property = $this->propertyRepository->updateStatus($id, PropertyStatus::ARCHIVED);
        PropertyArchived::dispatch($property);

        return $this->propertyRepository->findWithRelations($id);
    }

    public function updateStatus(int $id, PropertyStatus $newStatus, ?int $reviewerId = null, ?string $rejectionReason = null): Property
    {
        $property = $this->propertyRepository->findByIdOrFail($id);
        $this->validateTransition($property, $newStatus);

        if ($newStatus === PropertyStatus::REJECTED && $rejectionReason) {
            $property->update(['rejection_reason' => $rejectionReason]);
        }

        $property = $this->propertyRepository->updateStatus($id, $newStatus, $reviewerId);

        match ($newStatus) {
            PropertyStatus::PENDING_APPROVAL => PropertySubmitted::dispatch($property),
            PropertyStatus::APPROVED => PropertyApproved::dispatch($property, $reviewerId ?? 0),
            PropertyStatus::REJECTED => PropertyRejected::dispatch($property, $reviewerId ?? 0, $rejectionReason ?? ''),
            PropertyStatus::PUBLISHED => PropertyPublished::dispatch($property),
            PropertyStatus::ARCHIVED => PropertyArchived::dispatch($property),
            default => null,
        };

        return $this->propertyRepository->findWithRelations($id);
    }

    // ── Image Management ──

    public function addImage(int $propertyId, array $data): PropertyImage
    {
        $this->propertyRepository->findByIdOrFail($propertyId);

        $count = $this->propertyImageRepository->countByProperty($propertyId);
        if ($count >= 50) {
            throw new DomainException('Maximum of 50 images per property exceeded.', 422);
        }

        $data['property_id'] = $propertyId;
        $data['sort_order'] = $data['sort_order'] ?? $count;

        if (($data['is_primary'] ?? false) && $count > 0) {
            $this->propertyImageRepository->setPrimary($propertyId, 0); // clear all primary
        }

        if ($count === 0) {
            $data['is_primary'] = true;
        }

        return $this->propertyImageRepository->create($data);
    }

    public function updateImage(int $propertyId, int $imageId, array $data): PropertyImage
    {
        $image = $this->propertyImageRepository->findByPropertyAndId($propertyId, $imageId);
        if (! $image) {
            throw new DomainException('Image not found for this property.', 404);
        }

        if (($data['is_primary'] ?? false)) {
            $this->propertyImageRepository->setPrimary($propertyId, $imageId);
            unset($data['is_primary']);
        }

        $image->update($data);

        return $image->fresh();
    }

    public function deleteImage(int $propertyId, int $imageId): bool
    {
        $image = $this->propertyImageRepository->findByPropertyAndId($propertyId, $imageId);
        if (! $image) {
            throw new DomainException('Image not found for this property.', 404);
        }

        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $firstImage = $this->propertyImageRepository->findByProperty($propertyId)->first();
            if ($firstImage) {
                $firstImage->update(['is_primary' => true]);
            }
        }

        return true;
    }

    public function deleteAllImages(int $propertyId): int
    {
        $this->propertyRepository->findByIdOrFail($propertyId);

        return $this->propertyImageRepository->deleteByProperty($propertyId);
    }

    public function reorderImages(int $propertyId, array $orderedIds): void
    {
        $this->propertyRepository->findByIdOrFail($propertyId);
        $this->propertyImageRepository->reorder($propertyId, $orderedIds);
    }

    public function setPrimaryImage(int $propertyId, int $imageId): void
    {
        $image = $this->propertyImageRepository->findByPropertyAndId($propertyId, $imageId);
        if (! $image) {
            throw new DomainException('Image not found for this property.', 404);
        }

        $this->propertyImageRepository->setPrimary($propertyId, $imageId);
    }

    public function getImageCount(int $propertyId): int
    {
        return $this->propertyImageRepository->countByProperty($propertyId);
    }

    // ── Private helpers ──

    private function validateTransition(Property $property, PropertyStatus $newStatus): void
    {
        if (! $property->status->canTransitionTo($newStatus)) {
            throw new DomainException(
                "Cannot transition from {$property->status->value} to {$newStatus->value}.",
                422,
            );
        }
    }
}
