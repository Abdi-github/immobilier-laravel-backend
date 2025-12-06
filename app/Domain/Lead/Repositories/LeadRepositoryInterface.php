<?php

declare(strict_types=1);

namespace App\Domain\Lead\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LeadRepositoryInterface extends RepositoryInterface
{
    public function findByAgency(int $agencyId, array $filters = []): LengthAwarePaginator;

    public function findByProperty(int $propertyId, array $filters = []): LengthAwarePaginator;

    public function findAssignedTo(int $userId, array $filters = []): LengthAwarePaginator;

    public function findNeedingFollowUp(array $filters = []): LengthAwarePaginator;

    public function getStatistics(?int $agencyId = null): array;
}
