<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePropertyOwnership
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

        // Admins bypass ownership check
        $userType = $user->user_type instanceof UserType
            ? $user->user_type
            : UserType::tryFrom($user->user_type);
        if ($userType?->isAdmin()) {
            return $next($request);
        }

        $property = $request->route('property');

        if (! $property) {
            return $next($request);
        }

        // Property can be a model instance or an ID depending on route binding
        $propertyModel = is_object($property) ? $property : null;

        if (! $propertyModel) {
            return $next($request);
        }

        // Check ownership via agency or direct ownership
        $isOwner = $propertyModel->owner_id === $user->id;
        $isAgencyMember = $user->agency_id && $propertyModel->agency_id === $user->agency_id;

        if (! $isOwner && ! $isAgencyMember) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not own this property.',
            ], 403);
        }

        return $next($request);
    }
}
