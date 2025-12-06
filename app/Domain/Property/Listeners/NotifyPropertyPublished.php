<?php

declare(strict_types=1);

namespace App\Domain\Property\Listeners;

use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\PropertyPublished as PropertyPublishedMail;
use App\Domain\Property\Events\PropertyPublished;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPropertyPublished implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(PropertyPublished $event): void
    {
        $property = $event->property;
        $owner = $property->owner;

        if (!$owner) {
            return;
        }

        SendEmailJob::dispatch(
            new PropertyPublishedMail(
                recipientEmail: $owner->email,
                property: $property,
                locale: $owner->preferred_language ?? 'en',
            ),
        );
    }
}
