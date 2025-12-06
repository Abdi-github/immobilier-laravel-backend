<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Property\Models\Property;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\User\Models\Alert;
use App\Domain\User\Models\User;
use App\Domain\User\Repositories\AlertRepositoryInterface;
use App\Domain\User\Repositories\FavoriteRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly FavoriteRepositoryInterface $favoriteRepository,
        private readonly AlertRepositoryInterface $alertRepository,
    ) {}

    // ── Profile ──────────────────────────────────────────

    public function getProfile(User $user): User
    {
        $user->load(['agency', 'roles']);

        return $user;
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh(['agency', 'roles']);
    }

    // ── Settings ─────────────────────────────────────────

    public function getSettings(User $user): array
    {
        return [
            'preferred_language' => $user->preferred_language,
            'notification_preferences' => $user->notification_preferences ?? [
                'email_new_properties' => true,
                'email_price_changes' => true,
                'email_favorites_updates' => true,
                'email_newsletter' => false,
                'push_enabled' => false,
            ],
            'currency' => 'CHF',
        ];
    }

    public function updateSettings(User $user, array $data): array
    {
        $updateData = [];

        if (isset($data['preferred_language'])) {
            $updateData['preferred_language'] = $data['preferred_language'];
        }

        if (isset($data['notification_preferences'])) {
            $defaults = [
                'email_new_properties' => true,
                'email_price_changes' => true,
                'email_favorites_updates' => true,
                'email_newsletter' => false,
                'push_enabled' => false,
            ];
            $current = $user->notification_preferences ?? $defaults;
            $updateData['notification_preferences'] = array_merge($current, $data['notification_preferences']);
        }

        if (! empty($updateData)) {
            $user->update($updateData);
        }

        return $this->getSettings($user->fresh());
    }

    // ── Avatar ───────────────────────────────────────────

    public function updateAvatar(User $user, string $avatarUrl): User
    {
        $user->update(['avatar_url' => $avatarUrl]);

        return $user->fresh();
    }

    public function removeAvatar(User $user): User
    {
        $user->update(['avatar_url' => null]);

        return $user->fresh();
    }

    // ── Account ──────────────────────────────────────────

    public function deactivateAccount(User $user): void
    {
        $user->update(['status' => 'inactive']);
        $user->tokens()->delete();
        $user->delete(); // soft delete
    }

    // ── Dashboard ────────────────────────────────────────

    public function getDashboardStats(User $user): array
    {
        $favoritesCount = $user->favorites()->count();
        $alertsCount = $user->alerts()->count();
        $activeAlertsCount = $user->alerts()->where('is_active', true)->count();
        $propertiesCount = $user->properties()->count();

        $recentActivity = $user->favorites()
            ->with('property.primaryImage', 'property.city', 'property.translations')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($fav) => [
                'type' => 'favorite_added',
                'property_id' => (string) $fav->property_id,
                'property_title' => $fav->property?->translations
                    ->firstWhere('language', app()->getLocale())?->title
                    ?? $fav->property?->translations->first()?->title,
                'created_at' => $fav->created_at?->toISOString(),
            ]);

        return [
            'total_favorites' => $favoritesCount,
            'total_alerts' => $alertsCount,
            'active_alerts' => $activeAlertsCount,
            'total_properties' => $propertiesCount,
            'recent_activity' => $recentActivity,
        ];
    }

    // ── Favorites ────────────────────────────────────────

    public function getFavorites(int $userId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        return $this->favoriteRepository->findByUserPaginated($userId, $limit);
    }

    public function addFavorite(int $userId, int $propertyId): void
    {
        if (! Property::where('id', $propertyId)->exists()) {
            throw new DomainException(__('properties.not_found'), 404);
        }

        if ($this->favoriteRepository->isFavorited($userId, $propertyId)) {
            throw new DomainException(__('users.already_favorited'), 409);
        }

        $this->favoriteRepository->create([
            'user_id' => $userId,
            'property_id' => $propertyId,
        ]);
    }

    public function removeFavorite(int $userId, int $propertyId): void
    {
        if (! $this->favoriteRepository->isFavorited($userId, $propertyId)) {
            throw new DomainException(__('users.not_favorited'), 404);
        }

        $this->favoriteRepository->findByUser($userId)
            ->where('property_id', $propertyId)
            ->first()
            ->delete();
    }

    public function isFavorite(int $userId, int $propertyId): bool
    {
        return $this->favoriteRepository->isFavorited($userId, $propertyId);
    }

    public function getFavoriteIds(int $userId): array
    {
        return $this->favoriteRepository->getFavoritePropertyIds($userId);
    }

    // ── Alerts ───────────────────────────────────────────

    public function getAlerts(int $userId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        return Alert::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function getAlertById(int $id, int $userId): Alert
    {
        $alert = Alert::find($id);

        if (! $alert || $alert->user_id !== $userId) {
            throw new DomainException(__('users.alert_not_found'), 404);
        }

        return $alert;
    }

    public function createAlert(int $userId, array $data): Alert
    {
        $data['user_id'] = $userId;

        return Alert::create($data);
    }

    public function updateAlert(int $id, int $userId, array $data): Alert
    {
        $alert = $this->getAlertById($id, $userId);
        $alert->update($data);

        return $alert->fresh();
    }

    public function deleteAlert(int $id, int $userId): void
    {
        $alert = $this->getAlertById($id, $userId);
        $alert->delete();
    }

    public function toggleAlert(int $id, int $userId): Alert
    {
        $alert = $this->getAlertById($id, $userId);
        $alert->update(['is_active' => ! $alert->is_active]);

        return $alert->fresh();
    }
}
