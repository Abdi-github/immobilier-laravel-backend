<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\User\Models\User;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findByVerificationToken(string $token): ?User;

    public function findByPasswordResetToken(string $token): ?User;

    public function getStatistics(): array;
}
