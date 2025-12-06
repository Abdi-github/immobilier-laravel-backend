<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\PasswordChanged;
use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\PasswordChanged as PasswordChangedMail;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPasswordChangedEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(PasswordChanged $event): void
    {
        SendEmailJob::dispatch(
            new PasswordChangedMail(
                recipientEmail: $event->email,
                firstName: $event->firstName,
                locale: $event->preferredLanguage,
            ),
        );
    }
}
