<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

use Spatie\LaravelData\Data;

final class LoginData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}
