<?php

declare(strict_types=1);

namespace App\Domain\Property\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;

interface PropertyImageRepositoryInterface extends RepositoryInterface
{
    public function findByProperty(int $propertyId): \Illuminate\Database\Eloquent\Collection;

    public function reorder(int $propertyId, array $orderedIds): void;

    public function setPrimary(int $propertyId, int $imageId): void;

    public function countByProperty(int $propertyId): int;

    public function deleteByProperty(int $propertyId): int;

    public function findByPropertyAndId(int $propertyId, int $imageId): ?\App\Domain\Property\Models\PropertyImage;
}
