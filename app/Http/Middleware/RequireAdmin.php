<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            return redirect()->route('login');
        }

        $userType = $user->user_type instanceof UserType
            ? $user->user_type
            : UserType::tryFrom($user->user_type);

        if (! $userType || ! $userType->isAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Admin access required.',
                ], 403);
            }

            abort(403, 'Admin access required.');
        }

        return $next($request);
    }
}
