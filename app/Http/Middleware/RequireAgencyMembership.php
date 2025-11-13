<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireAgencyMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Admins bypass agency membership check
        $userType = $user->user_type instanceof UserType
            ? $user->user_type
            : UserType::tryFrom($user->user_type);
        if ($userType?->isAdmin()) {
            return $next($request);
        }

        if (! $user->agency_id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Agency membership required.',
            ], 403);
        }

        return $next($request);
    }
}
