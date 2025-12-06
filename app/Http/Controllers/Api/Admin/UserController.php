<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\UserType;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class UserController extends Controller
{
    use ApiResponse;

    public function statistics(): JsonResponse
    {
        // \Log::debug('Fetching user statistics');
        
        $this->authorize('users:read');

        $totalUsers = User::count();
        $byType = User::selectRaw('user_type, COUNT(*) as count')
            ->groupBy('user_type')
            ->pluck('count', 'user_type');

        $byStatus = User::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentUsers = User::where('created_at', '>=', now()->subDays(30))->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        // \Log::debug('Stats computed', ['total' => $totalUsers, 'types' => count($byType)]);

        return $this->successResponse([
            'total_users' => $totalUsers,
            'by_type' => $byType,
            'by_status' => $byStatus,
            'recent_users' => $recentUsers,
            'verified_users' => $verifiedUsers,
        ], __('users.statistics_retrieved'));
    }

    public function index(Request $request): JsonResponse
    {
        // \Log::debug('User list request', ['filters' => array_keys($request->query())]);
        
        $this->authorize('users:read');

        $query = User::query()
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('email', 'ILIKE', "%{$s}%")
                    ->orWhere('first_name', 'ILIKE', "%{$s}%")
                    ->orWhere('last_name', 'ILIKE', "%{$s}%");
            }))
            ->when($request->query('user_type'), fn ($q, $t) => $q->where('user_type', $t))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('agency_id'), fn ($q, $id) => $q->where('agency_id', $id))
            ->when($request->has('email_verified'), fn ($q) => filter_var($request->query('email_verified'), FILTER_VALIDATE_BOOLEAN)
                ? $q->whereNotNull('email_verified_at')
                : $q->whereNull('email_verified_at')
            )
            ->with(['agency', 'roles']);

        $sort = $request->query('sort', 'created_at');
        $order = $request->query('order', 'desc');
        $query->orderBy($sort, $order);
        // \Log::debug('Query built', ['sort' => $sort, 'order' => $order]);

        $users = $query->paginate(
            (int) $request->query('limit', '20'),
            ['*'],
            'page',
            (int) $request->query('page', '1'),
        );
        // \Log::debug('Results fetched', ['count' => $users->count(), 'total' => $users->total()]);

        return $this->paginatedResponse(
            $users->through(fn ($u) => new UserResource($u)),
            __('users.users_listed'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('users:read');

        $user = User::with(['agency', 'roles'])->find($id);
        if (! $user) {
            return $this->notFoundResponse(__('users.user_not_found'));
        }

        return $this->successResponse(new UserResource($user), __('users.user_retrieved'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('users:create');

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'user_type' => 'required|string|in:' . implode(',', array_column(UserType::cases(), 'value')),
            'agency_id' => 'nullable|integer|exists:agencies,id',
            'preferred_language' => 'sometimes|string|in:en,fr,de,it',
            'status' => 'sometimes|string|in:' . implode(',', array_column(AccountStatus::cases(), 'value')),
            'role' => 'sometimes|string|exists:roles,name',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'user_type' => $validated['user_type'],
            'agency_id' => $validated['agency_id'] ?? null,
            'preferred_language' => $validated['preferred_language'] ?? 'en',
            'status' => $validated['status'] ?? AccountStatus::ACTIVE->value,
        ]);

        $roleName = $validated['role'] ?? $validated['user_type'];
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->assignRole($role);
        }

        $user->load(['agency', 'roles']);

        return $this->createdResponse(new UserResource($user), __('users.user_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('users:manage');

        $user = User::find($id);
        if (! $user) {
            return $this->notFoundResponse(__('users.user_not_found'));
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:50',
            'user_type' => 'sometimes|string|in:' . implode(',', array_column(UserType::cases(), 'value')),
            'agency_id' => 'nullable|integer|exists:agencies,id',
            'preferred_language' => 'sometimes|string|in:en,fr,de,it',
            'status' => 'sometimes|string|in:' . implode(',', array_column(AccountStatus::cases(), 'value')),
            'role' => 'sometimes|string|exists:roles,name',
        ]);

        $roleToSync = null;
        if (isset($validated['role'])) {
            $roleToSync = $validated['role'];
            unset($validated['role']);
        }

        $user->update($validated);

        if ($roleToSync) {
            $user->syncRoles([$roleToSync]);
        }

        $user->load(['agency', 'roles']);

        return $this->successResponse(new UserResource($user), __('users.user_updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $this->authorize('users:manage');

        $user = User::find($id);
        if (! $user) {
            return $this->notFoundResponse(__('users.user_not_found'));
        }

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(AccountStatus::cases(), 'value')),
        ]);

        $user->update(['status' => $validated['status']]);

        $user->load(['agency', 'roles']);

        return $this->successResponse(new UserResource($user), __('users.status_updated'));
    }

    public function suspend(int $id): JsonResponse
    {
        $this->authorize('users:manage');

        $user = User::find($id);
        if (! $user) {
            return $this->notFoundResponse(__('users.user_not_found'));
        }

        $user->update(['status' => AccountStatus::SUSPENDED->value]);
        $user->load(['agency', 'roles']);

        return $this->successResponse(new UserResource($user), __('users.user_suspended'));
    }

    public function activate(int $id): JsonResponse
    {
        $this->authorize('users:manage');

        $user = User::find($id);
        if (! $user) {
            return $this->notFoundResponse(__('users.user_not_found'));
        }

        $user->update(['status' => AccountStatus::ACTIVE->value]);
        $user->load(['agency', 'roles']);

        return $this->successResponse(new UserResource($user), __('users.user_activated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('users:delete');

        $user = User::find($id);
        if (! $user) {
            return $this->notFoundResponse(__('users.user_not_found'));
        }

        $user->delete();

        return $this->successResponse(null, __('users.user_deleted'));
    }
}
