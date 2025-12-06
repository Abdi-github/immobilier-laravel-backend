<?php

declare(strict_types=1);

namespace App\Domain\Property\Listeners;

use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\PropertyRejected as PropertyRejectedMail;
use App\Domain\Property\Events\PropertyRejected;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPropertyRejected implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(PropertyRejected $event): void
    {
        $property = $event->property;
        $owner = $property->owner;

        if (!$owner) {
            return;
        }

        SendEmailJob::dispatch(
            new PropertyRejectedMail(
                recipientEmail: $owner->email,
                property: $property,
                reason: $event->reason,
                locale: $owner->preferred_language ?? 'en',
            ),
        );
    }
}
