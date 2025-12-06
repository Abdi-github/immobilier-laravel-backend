<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configurePermissionGate();
    }

    /**
     * RBAC permission gate with wildcard + manage resolution.
     *
     * Resolution order:
     * 1. Wildcard '*' → grants everything (super_admin)
     * 2. Direct match (e.g. 'properties:read')
     * 3. Resource wildcard (e.g. 'properties:*')
     * 4. Manage grants all (e.g. 'properties:manage' → all property actions)
     */
    private function configurePermissionGate(): void
    {
        Gate::before(function ($user, string $ability) {
            // 1. Wildcard — super_admin has '*' permission
            try {
                if ($user->hasPermissionTo('*')) {
                    return true;
                }
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
                // Permission not registered, skip
            }

            // 2. Direct match — handled by Spatie's default
            try {
                if ($user->hasPermissionTo($ability)) {
                    return true;
                }
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
                // Permission not registered, skip
            }

            // Parse resource:action format
            if (! str_contains($ability, ':')) {
                return null;
            }

            [$resource, $action] = explode(':', $ability, 2);

            // 3. Resource wildcard (e.g. properties:*)
            try {
                if ($user->hasPermissionTo("{$resource}:*")) {
                    return true;
                }
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
                // Permission not registered, skip
            }

            // 4. Manage grants all actions on the resource
            try {
                if ($action !== 'manage' && $user->hasPermissionTo("{$resource}:manage")) {
                    return true;
                }
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
                // Permission not registered, skip
            }

            return null;
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinutes(
                15,
                config('immobilier.rate_limits.public', 100),
            )->by($request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinutes(
                15,
                config('immobilier.rate_limits.auth', 300),
            )->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinutes(
                15,
                config('immobilier.rate_limits.admin', 1000),
            )->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('strict', function (Request $request) {
            return Limit::perMinutes(
                15,
                config('immobilier.rate_limits.strict', 5),
            )->by($request->ip());
        });
    }
}
