<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\User\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlertResource;
use App\Http\Resources\FavoriteResource;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserService $userService,
    ) {}

    // ── Profile ──────────────────────────────────────────

    public function getProfile(Request $request): JsonResponse
    {
        $user = $this->userService->getProfile($request->user());

        return $this->successResponse(new UserResource($user), __('common.retrieved'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:50',
            'preferred_language' => 'sometimes|string|in:en,fr,de,it',
        ]);

        $user = $this->userService->updateProfile($request->user(), $validated);

        return $this->successResponse(new UserResource($user), __('users.updated'));
    }

    // ── Settings ─────────────────────────────────────────

    public function getSettings(Request $request): JsonResponse
    {
        $settings = $this->userService->getSettings($request->user());

        return $this->successResponse($settings, __('common.retrieved'));
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferred_language' => 'sometimes|string|in:en,fr,de,it',
            'notification_preferences' => 'sometimes|array',
            'notification_preferences.email_new_properties' => 'sometimes|boolean',
            'notification_preferences.email_price_changes' => 'sometimes|boolean',
            'notification_preferences.email_favorites_updates' => 'sometimes|boolean',
            'notification_preferences.email_newsletter' => 'sometimes|boolean',
            'notification_preferences.push_enabled' => 'sometimes|boolean',
        ]);

        $settings = $this->userService->updateSettings($request->user(), $validated);

        return $this->successResponse($settings, __('users.settings_updated'));
    }

    // ── Avatar ───────────────────────────────────────────

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:5120|mimes:jpeg,png,webp',
        ]);

        // CloudinaryService is Phase 7. For now, store avatar URL from file.
        // When CloudinaryService is ready, upload here and get URL back.
        $file = $request->file('avatar');
        $path = $file->store('avatars', 'public');
        $avatarUrl = asset('storage/'.$path);

        $user = $this->userService->updateAvatar($request->user(), $avatarUrl);

        return $this->successResponse(new UserResource($user), __('users.avatar_uploaded'));
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $this->userService->removeAvatar($request->user());

        return $this->successResponse(new UserResource($user), __('users.avatar_removed'));
    }

    // ── Account ──────────────────────────────────────────

    public function deactivate(Request $request): JsonResponse
    {
        $this->userService->deactivateAccount($request->user());

        return $this->successResponse(null, __('users.deactivated'));
    }

    // ── Dashboard ────────────────────────────────────────

    public function dashboardStats(Request $request): JsonResponse
    {
        $stats = $this->userService->getDashboardStats($request->user());

        return $this->successResponse($stats, __('common.retrieved'));
    }

    // ── Favorites ────────────────────────────────────────

    public function listFavorites(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', '20');
        $page = (int) $request->query('page', '1');

        $favorites = $this->userService->getFavorites($request->user()->id, $page, $limit);

        return $this->paginatedResponse(
            $favorites->through(fn ($f) => new FavoriteResource($f)),
            __('common.retrieved'),
        );
    }

    public function addFavorite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => 'required|integer',
        ]);

        try {
            $this->userService->addFavorite($request->user()->id, $validated['property_id']);

            return $this->createdResponse(null, __('users.favorite_added'));
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function removeFavorite(Request $request, int $propertyId): JsonResponse
    {
        try {
            $this->userService->removeFavorite($request->user()->id, $propertyId);

            return $this->successResponse(null, __('users.favorite_removed'));
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function checkFavorite(Request $request, int $propertyId): JsonResponse
    {
        $isFavorite = $this->userService->isFavorite($request->user()->id, $propertyId);

        return $this->successResponse(['is_favorite' => $isFavorite], __('common.retrieved'));
    }

    public function favoriteIds(Request $request): JsonResponse
    {
        $ids = $this->userService->getFavoriteIds($request->user()->id);
        $stringIds = array_map('strval', $ids);

        return $this->successResponse(['property_ids' => $stringIds], __('common.retrieved'));
    }

    // ── Alerts ───────────────────────────────────────────

    public function listAlerts(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', '20');
        $page = (int) $request->query('page', '1');

        $alerts = $this->userService->getAlerts($request->user()->id, $page, $limit);

        return $this->paginatedResponse(
            $alerts->through(fn ($a) => new AlertResource($a)),
            __('common.retrieved'),
        );
    }

    public function createAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'criteria' => 'required|array',
            'criteria.transaction_type' => 'nullable|string|in:rent,buy',
            'criteria.category_id' => 'nullable|integer',
            'criteria.canton_id' => 'nullable|integer',
            'criteria.city_id' => 'nullable|integer',
            'criteria.price_min' => 'nullable|numeric|min:0',
            'criteria.price_max' => 'nullable|numeric|min:0',
            'criteria.rooms_min' => 'nullable|numeric|min:0',
            'criteria.rooms_max' => 'nullable|numeric|min:0',
            'criteria.surface_min' => 'nullable|numeric|min:0',
            'criteria.surface_max' => 'nullable|numeric|min:0',
            'criteria.amenities' => 'nullable|array',
            'criteria.amenities.*' => 'integer',
            'frequency' => 'nullable|string|in:instant,daily,weekly',
            'is_active' => 'nullable|boolean',
        ]);

        $alert = $this->userService->createAlert($request->user()->id, $validated);

        return $this->createdResponse(new AlertResource($alert), __('users.alert_created'));
    }

    public function showAlert(Request $request, int $id): JsonResponse
    {
        try {
            $alert = $this->userService->getAlertById($id, $request->user()->id);

            return $this->successResponse(new AlertResource($alert), __('common.retrieved'));
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function updateAlert(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'criteria' => 'sometimes|array',
            'criteria.transaction_type' => 'nullable|string|in:rent,buy',
            'criteria.category_id' => 'nullable|integer',
            'criteria.canton_id' => 'nullable|integer',
            'criteria.city_id' => 'nullable|integer',
            'criteria.price_min' => 'nullable|numeric|min:0',
            'criteria.price_max' => 'nullable|numeric|min:0',
            'criteria.rooms_min' => 'nullable|numeric|min:0',
            'criteria.rooms_max' => 'nullable|numeric|min:0',
            'criteria.surface_min' => 'nullable|numeric|min:0',
            'criteria.surface_max' => 'nullable|numeric|min:0',
            'criteria.amenities' => 'nullable|array',
            'criteria.amenities.*' => 'integer',
            'frequency' => 'sometimes|string|in:instant,daily,weekly',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $alert = $this->userService->updateAlert($id, $request->user()->id, $validated);

            return $this->successResponse(new AlertResource($alert), __('users.alert_updated'));
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function deleteAlert(Request $request, int $id): JsonResponse
    {
        try {
            $this->userService->deleteAlert($id, $request->user()->id);

            return $this->successResponse(null, __('users.alert_deleted'));
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function toggleAlert(Request $request, int $id): JsonResponse
    {
        try {
            $alert = $this->userService->toggleAlert($id, $request->user()->id);

            return $this->successResponse(new AlertResource($alert), __('users.alert_toggled'));
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }
}
