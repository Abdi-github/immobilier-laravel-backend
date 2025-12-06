<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\UserType;
use App\Domain\User\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Actions — no CreateNewUser (registration disabled for admin panel)
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);

        // Custom authentication logic: verify credentials + admin role + active status
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->input('email'))->first();

            if (! $user || ! Hash::check($request->input('password'), $user->password)) {
                return null;
            }

            // Must be an admin user type
            if (! $user->user_type->isAdmin()) {
                throw ValidationException::withMessages([
                    Fortify::username() => [__('auth.admin_only')],
                ]);
            }

            // Must have active account
            if ($user->status !== AccountStatus::ACTIVE) {
                throw ValidationException::withMessages([
                    Fortify::username() => [__('auth.account_status', ['status' => $user->status->value])],
                ]);
            }

            // Update last login
            $user->update(['last_login_at' => now()]);

            return $user;
        });

        // Rate limiting
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
