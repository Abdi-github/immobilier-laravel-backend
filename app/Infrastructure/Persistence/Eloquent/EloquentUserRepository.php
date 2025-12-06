<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepositoryInterface;

final class EloquentUserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByVerificationToken(string $token): ?User
    {
        return User::where('email_verification_token', $token)
            ->where('email_verification_expires_at', '>', now())
            ->first();
    }

    public function findByPasswordResetToken(string $token): ?User
    {
        return User::where('password_reset_token', $token)
            ->where('password_reset_expires_at', '>', now())
            ->first();
    }

    public function getStatistics(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'pending' => User::where('status', 'pending')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'by_type' => User::selectRaw('user_type, count(*) as count')
                ->groupBy('user_type')
                ->pluck('count', 'user_type')
                ->toArray(),
        ];
    }
}
