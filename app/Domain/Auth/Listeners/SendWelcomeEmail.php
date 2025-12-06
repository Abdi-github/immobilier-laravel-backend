<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\UserEmailVerified;
use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\WelcomeEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendWelcomeEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(UserEmailVerified $event): void
    {
        SendEmailJob::dispatch(
            new WelcomeEmail(
                recipientEmail: $event->email,
                firstName: $event->firstName,
                locale: $event->preferredLanguage,
            ),
        );
    }
}
