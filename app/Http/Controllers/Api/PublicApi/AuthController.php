<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Auth\DTOs\LoginData;
use App\Domain\Auth\DTOs\RegisterData;
use App\Domain\Auth\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;

final class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * POST /api/v1/public/auth/login
     */
    public function login(LoginRequest $request)
    {
        $dto = LoginData::from($request->validated());
        // \Log::debug('Login attempt', ['email' => $dto->email]);
        
        $result = $this->authService->login($dto);
        // \Log::debug('✓ Login successful', ['user' => $result['user']->id]);

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'tokens' => $result['tokens'],
        ], __('auth.login_success'));
    }

    /**
     * POST /api/v1/public/auth/register
     */
    public function register(RegisterRequest $request)
    {
        $dto = RegisterData::from($request->validated());
        // \Log::info('New registration', ['email' => $dto->email, 'type' => $dto->user_type]);
        
        $result = $this->authService->register($dto);
        // \Log::debug('✓ User registered', ['id' => $result['user']->id]);

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'tokens' => $result['tokens'],
        ], __('auth.register_success'), 201);
    }

    /**
     * POST /api/v1/public/auth/refresh
     */
    public function refresh(RefreshTokenRequest $request)
    {
        // \Log::debug('🔄 Token refresh request');
        
        $tokens = $this->authService->refreshToken($request->validated('refresh_token'));
        // \Log::debug('✓ Tokens refreshed');

        return $this->successResponse($tokens, __('auth.refresh_success'));
    }

    /**
     * POST /api/v1/public/auth/logout
     */
    public function logout()
    {
        // \Log::debug('Logout for user:', ['id' => request()->user()?->id]);
        
        $this->authService->logout(request()->user());
        // \Log::debug('✓ Logged out');

        return $this->successResponse(null, __('auth.logout_success'));
    }

    /**
     * GET /api/v1/public/auth/me
     */
    public function me()
    {
        $user = $this->authService->getProfile(request()->user());

        return $this->successResponse(new UserResource($user), __('auth.profile_retrieved'));
    }

    /**
     * PATCH /api/v1/public/auth/me
     */
    public function updateMe(UpdateProfileRequest $request)
    {
        $user = $this->authService->updateProfile(
            $request->user(),
            $request->validated(),
        );

        return $this->successResponse(new UserResource($user), __('auth.profile_updated'));
    }

    /**
     * POST /api/v1/public/auth/change-password
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $this->authService->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('new_password'),
        );

        return $this->successResponse(null, __('auth.password_changed'));
    }

    /**
     * POST /api/v1/public/auth/verify-email
     */
    public function verifyEmail(VerifyEmailRequest $request)
    {
        $result = $this->authService->verifyEmail($request->validated('token'));

        return $this->successResponse(null, $result['message']);
    }

    /**
     * POST /api/v1/public/auth/resend-verification
     */
    public function resendVerification(ResendVerificationRequest $request)
    {
        $result = $this->authService->resendVerification($request->validated('email'));

        return $this->successResponse(null, $result['message']);
    }

    /**
     * POST /api/v1/public/auth/forgot-password
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $result = $this->authService->forgotPassword($request->validated('email'));

        return $this->successResponse(null, $result['message']);
    }

    /**
     * POST /api/v1/public/auth/reset-password
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $result = $this->authService->resetPassword(
            $request->validated('token'),
            $request->validated('password'),
        );

        return $this->successResponse(null, $result['message']);
    }
}
