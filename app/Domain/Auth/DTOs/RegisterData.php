<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

use App\Domain\User\Enums\UserType;
use Spatie\LaravelData\Data;

final class RegisterData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly ?string $phone = null,
        public readonly ?UserType $user_type = null,
        public readonly ?string $preferred_language = null,
    ) {}
}
