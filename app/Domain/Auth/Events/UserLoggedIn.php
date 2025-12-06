<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
    ) {}
}
