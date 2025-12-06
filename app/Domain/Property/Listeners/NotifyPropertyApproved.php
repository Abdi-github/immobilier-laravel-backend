<?php

declare(strict_types=1);

namespace App\Domain\Property\Listeners;

use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\PropertyApproved as PropertyApprovedMail;
use App\Domain\Property\Events\PropertyApproved;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPropertyApproved implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(PropertyApproved $event): void
    {
        $property = $event->property;
        $owner = $property->owner;

        if (!$owner) {
            return;
        }

        SendEmailJob::dispatch(
            new PropertyApprovedMail(
                recipientEmail: $owner->email,
                property: $property,
                locale: $owner->preferred_language ?? 'en',
            ),
        );
    }
}
