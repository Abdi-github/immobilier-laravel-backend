<?php

declare(strict_types=1);

namespace App\Domain\Property\Listeners;

use App\Domain\Property\Events\PropertyPublished;
use Illuminate\Contracts\Queue\ShouldQueue;

final class RequestPropertyTranslations implements ShouldQueue
{
    public string $queue = 'translations';

    public function handle(PropertyPublished $event): void
    {
        // Phase 9: Dispatch translation jobs for missing languages via TranslationService
    }
}
