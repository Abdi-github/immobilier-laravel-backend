<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTOs\LoginData;
use App\Domain\Auth\DTOs\RegisterData;
use App\Domain\Auth\Events\PasswordChanged;
use App\Domain\Auth\Events\PasswordResetRequested;
use App\Domain\Auth\Events\UserEmailVerified;
use App\Domain\Auth\Events\UserLoggedIn;
use App\Domain\Auth\Events\UserRegistered;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\UserType;
use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Login user with email and password.
     *
     * @return array{user: User, tokens: array}
     */
    public function login(LoginData $dto): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->status !== AccountStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'email' => [__('auth.account_status', ['status' => $user->status->value])],
            ]);
        }

        // Generate token pair
        $tokens = $this->generateTokenPair($user);

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Load roles and permissions
        $user->load(['roles.permissions', 'agency']);

        UserLoggedIn::dispatch($user->id);

        return [
            'user' => $user,
            'tokens' => $tokens,
        ];
    }

    /**
     * Register a new user.
     *
     * @return array{user: User, tokens: array}
     */
    public function register(RegisterData $dto): array
    {
        // Check if email already exists
        $existing = $this->userRepository->findByEmail($dto->email);
        if ($existing) {
            throw ValidationException::withMessages([
                'email' => [__('auth.email_taken')],
            ]);
        }

        $userType = $dto->user_type ?? UserType::END_USER;

        // Professional accounts start pending, others active
        $status = in_array($userType, [UserType::AGENT, UserType::AGENCY_ADMIN])
            ? AccountStatus::PENDING
            : AccountStatus::ACTIVE;

        // Generate verification token
        $verificationToken = Str::random(64);

        $user = DB::transaction(function () use ($dto, $userType, $status, $verificationToken) {
            /** @var User $user */
            $user = $this->userRepository->create([
                'email' => $dto->email,
                'password' => $dto->password, // Hashed via cast
                'first_name' => $dto->first_name,
                'last_name' => $dto->last_name,
                'phone' => $dto->phone,
                'user_type' => $userType->value,
                'preferred_language' => $dto->preferred_language ?? 'en',
                'status' => $status->value,
                'email_verification_token' => hash('sha256', $verificationToken),
                'email_verification_expires_at' => now()->addHours(24),
            ]);

            // Assign default role based on user type
            $this->assignDefaultRole($user);

            return $user;
        });

        // Generate token pair
        $tokens = $this->generateTokenPair($user);

        // Load roles and permissions
        $user->load(['roles.permissions']);

        // Dispatch event for verification email
        UserRegistered::dispatch(
            $user->id,
            $user->email,
            $user->first_name,
            $verificationToken,
            $user->preferred_language ?? 'en',
        );

        return [
            'user' => $user,
            'tokens' => $tokens,
        ];
    }

    /**
     * Refresh access token using refresh token.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: string, token_type: string}
     */
    public function refreshToken(string $refreshToken): array
    {
        // Sanctum tokens are formatted as {id}|{plaintext}
        $parts = explode('|', $refreshToken, 2);
        if (count($parts) !== 2) {
            throw ValidationException::withMessages([
                'refresh_token' => [__('auth.invalid_refresh_token')],
            ]);
        }

        [$tokenId, $plaintext] = $parts;
        $tokenHash = hash('sha256', $plaintext);

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::where('id', $tokenId)
            ->where('token', $tokenHash)
            ->where('name', 'refresh_token')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $accessToken) {
            throw ValidationException::withMessages([
                'refresh_token' => [__('auth.invalid_refresh_token')],
            ]);
        }

        $user = $accessToken->tokenable;

        if (! $user || $user->status !== AccountStatus::ACTIVE) {
            $accessToken->delete();
            throw ValidationException::withMessages([
                'refresh_token' => [__('auth.invalid_refresh_token')],
            ]);
        }

        // Delete old refresh token (rotation)
        $accessToken->delete();

        // Generate new token pair
        return $this->generateTokenPair($user);
    }

    /**
     * Logout user (revoke current token).
     */
    public function logout(User $user): void
    {
        // Delete current access token
        $user->currentAccessToken()?->delete();

        // Also delete all refresh tokens for this user
        $user->tokens()->where('name', 'refresh_token')->delete();
    }

    /**
     * Get current user profile with roles and permissions.
     */
    public function getProfile(User $user): User
    {
        $user->load(['roles.permissions', 'agency']);

        return $user;
    }

    /**
     * Update current user profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $allowedFields = ['first_name', 'last_name', 'phone', 'preferred_language'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (! empty($updateData)) {
            $user->update($updateData);
        }

        $user->load(['roles.permissions', 'agency']);

        return $user->fresh();
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.current_password_incorrect')],
            ]);
        }

        $user->update([
            'password' => $newPassword, // Hashed via cast
            'password_changed_at' => now(),
        ]);

        // Revoke all tokens (force re-login)
        $user->tokens()->delete();

        PasswordChanged::dispatch(
            $user->id,
            $user->email,
            $user->first_name,
            $user->preferred_language ?? 'en',
        );
    }

    /**
     * Verify email with token.
     */
    public function verifyEmail(string $token): array
    {
        $tokenHash = hash('sha256', $token);
        $user = $this->userRepository->findByVerificationToken($tokenHash);

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => [__('auth.invalid_verification_token')],
            ]);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_expires_at' => null,
        ]);

        UserEmailVerified::dispatch(
            $user->id,
            $user->email,
            $user->first_name,
            $user->user_type->value,
            $user->preferred_language ?? 'en',
        );

        return ['message' => __('auth.email_verified')];
    }

    /**
     * Resend verification email.
     */
    public function resendVerification(string $email): array
    {
        $user = $this->userRepository->findByEmail($email);

        // Don't reveal if user exists (security)
        if (! $user || $user->email_verified_at !== null) {
            return ['message' => __('auth.verification_resent')];
        }

        $verificationToken = Str::random(64);
        $user->update([
            'email_verification_token' => hash('sha256', $verificationToken),
            'email_verification_expires_at' => now()->addHours(24),
        ]);

        UserRegistered::dispatch(
            $user->id,
            $user->email,
            $user->first_name,
            $verificationToken,
            $user->preferred_language ?? 'en',
        );

        return ['message' => __('auth.verification_resent')];
    }

    /**
     * Request password reset.
     */
    public function forgotPassword(string $email): array
    {
        $user = $this->userRepository->findByEmail($email);

        // Don't reveal if user exists (security)
        if (! $user) {
            return ['message' => __('auth.password_reset_sent')];
        }

        $resetToken = Str::random(64);
        $user->update([
            'password_reset_token' => hash('sha256', $resetToken),
            'password_reset_expires_at' => now()->addHour(),
        ]);

        PasswordResetRequested::dispatch(
            $user->id,
            $user->email,
            $user->first_name,
            $resetToken,
            $user->preferred_language ?? 'en',
        );

        return ['message' => __('auth.password_reset_sent')];
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $tokenHash = hash('sha256', $token);
        $user = $this->userRepository->findByPasswordResetToken($tokenHash);

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => [__('auth.invalid_reset_token')],
            ]);
        }

        $user->update([
            'password' => $newPassword, // Hashed via cast
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
            'password_changed_at' => now(),
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

        PasswordChanged::dispatch(
            $user->id,
            $user->email,
            $user->first_name,
            $user->preferred_language ?? 'en',
        );

        return ['message' => __('auth.password_reset_success')];
    }

    /**
     * Generate access + refresh token pair.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: string, token_type: string}
     */
    private function generateTokenPair(User $user): array
    {
        // Delete any existing tokens for this user (single session)
        $user->tokens()->delete();

        $expiresIn = config('immobilier.auth.token_expiration', '7d');
        $expiresAt = $this->parseExpiration($expiresIn);

        // Access token
        $accessToken = $user->createToken(
            'access_token',
            ['*'],
            $expiresAt,
        );

        // Refresh token (longer lived)
        $refreshExpiresIn = config('immobilier.auth.refresh_token_expiration', '30d');
        $refreshExpiresAt = $this->parseExpiration($refreshExpiresIn);

        $refreshToken = $user->createToken(
            'refresh_token',
            ['refresh'],
            $refreshExpiresAt,
        );

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'expires_in' => $expiresIn,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Parse expiration string (e.g. "7d", "24h") to Carbon date.
     */
    private function parseExpiration(string $expiration): \Carbon\Carbon
    {
        $match = [];
        if (preg_match('/^(\d+)([smhd])$/', $expiration, $match)) {
            $value = (int) $match[1];

            return match ($match[2]) {
                's' => now()->addSeconds($value),
                'm' => now()->addMinutes($value),
                'h' => now()->addHours($value),
                'd' => now()->addDays($value),
            };
        }

        return now()->addDays(7);
    }

    /**
     * Assign default role based on user type.
     */
    private function assignDefaultRole(User $user): void
    {
        $roleMap = [
            UserType::END_USER->value => 'end_user',
            UserType::OWNER->value => 'owner',
            UserType::AGENT->value => 'agent',
            UserType::AGENCY_ADMIN->value => 'agency_admin',
            UserType::PLATFORM_ADMIN->value => 'platform_admin',
            UserType::SUPER_ADMIN->value => 'super_admin',
        ];

        $roleName = $roleMap[$user->user_type->value] ?? 'end_user';
        $user->assignRole($roleName);
    }
}
