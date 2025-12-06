<?php

declare(strict_types=1);

namespace App\Domain\Property\Listeners;

use App\Domain\Property\Events\PropertyArchived;
use Illuminate\Contracts\Queue\ShouldQueue;

final class RemovePropertyFromSearch implements ShouldQueue
{
    public string $queue = 'search';

    public function handle(PropertyArchived $event): void
    {
        // Phase 10: Remove property from Meilisearch via Scout
    }
}
