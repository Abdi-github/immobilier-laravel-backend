<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\UserType;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'user_type' => UserType::END_USER,
            'preferred_language' => 'en',
            'status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'user_type' => UserType::SUPER_ADMIN,
        ]);
    }

    public function platformAdmin(): static
    {
        return $this->state(fn () => [
            'user_type' => UserType::PLATFORM_ADMIN,
        ]);
    }

    public function agent(): static
    {
        return $this->state(fn () => [
            'user_type' => UserType::AGENT,
        ]);
    }

    public function agencyAdmin(): static
    {
        return $this->state(fn () => [
            'user_type' => UserType::AGENCY_ADMIN,
        ]);
    }

    public function owner(): static
    {
        return $this->state(fn () => [
            'user_type' => UserType::OWNER,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::PENDING,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::SUSPENDED,
        ]);
    }
}
