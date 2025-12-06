<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\UserRegistered;
use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendVerificationEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(UserRegistered $event): void
    {
        SendEmailJob::dispatch(
            new VerifyEmail(
                recipientEmail: $event->email,
                firstName: $event->firstName,
                verificationToken: $event->verificationToken,
                locale: $event->preferredLanguage,
            ),
        );
    }
}
