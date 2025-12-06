<?php

declare(strict_types=1);

namespace App\Domain\Property\Listeners;

use App\Domain\Property\Events\PropertyCreated;
use App\Domain\Property\Events\PropertyApproved;
use App\Domain\Property\Events\PropertyPublished;
use Illuminate\Contracts\Queue\ShouldQueue;

final class IndexPropertyInSearch implements ShouldQueue
{
    public string $queue = 'search';

    public function handle(PropertyCreated|PropertyApproved|PropertyPublished $event): void
    {
        // Phase 10: Index property in Meilisearch via Scout
    }
}
