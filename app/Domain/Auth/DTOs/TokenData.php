<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

use Spatie\LaravelData\Data;

final class TokenData extends Data
{
    public function __construct(
        public readonly string $access_token,
        public readonly string $refresh_token,
        public readonly string $expires_in,
        public readonly string $token_type = 'Bearer',
    ) {}
}
