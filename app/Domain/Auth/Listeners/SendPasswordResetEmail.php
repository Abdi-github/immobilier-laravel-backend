<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\PasswordResetRequested;
use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\PasswordReset;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPasswordResetEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(PasswordResetRequested $event): void
    {
        SendEmailJob::dispatch(
            new PasswordReset(
                recipientEmail: $event->email,
                firstName: $event->firstName,
                resetToken: $event->resetToken,
                locale: $event->preferredLanguage,
            ),
        );
    }
}
