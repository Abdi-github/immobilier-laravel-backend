<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PasswordResetRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $resetToken,
        public readonly string $preferredLanguage,
    ) {}
}
