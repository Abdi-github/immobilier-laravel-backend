<?php

declare(strict_types=1);

namespace App\Domain\Property\Listeners;

use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\PropertySubmitted as PropertySubmittedMail;
use App\Domain\Property\Events\PropertySubmitted;
use App\Domain\User\Enums\UserType;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPropertySubmitted implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(PropertySubmitted $event): void
    {
        $admins = User::whereIn('user_type', [
            UserType::SUPER_ADMIN->value,
            UserType::PLATFORM_ADMIN->value,
        ])->get();

        foreach ($admins as $admin) {
            SendEmailJob::dispatch(
                new PropertySubmittedMail(
                    recipientEmail: $admin->email,
                    property: $event->property,
                    locale: $admin->preferred_language ?? 'en',
                ),
            );
        }
    }
}
