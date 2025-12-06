<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('admin:read');

        $query = Role::query()
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'ILIKE', "%{$s}%")
                    ->orWhereRaw("display_name::text ILIKE ?", ["%{$s}%"]);
            }))
            ->when($request->has('is_system'), fn ($q) => $q->where('is_system', filter_var($request->query('is_system'), FILTER_VALIDATE_BOOLEAN)))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)));

        if (filter_var($request->query('include_permissions', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            $query->with('permissions');
        } else {
            $query->withCount('permissions');
        }

        $sort = $request->query('sort', 'name');
        $order = $request->query('order', 'asc');
        $query->orderBy($sort, $order);

        $roles = $query->paginate(
            (int) $request->query('limit', '20'),
            ['*'],
            'page',
            (int) $request->query('page', '1'),
        );

        return $this->paginatedResponse(
            $roles->through(fn ($r) => new RoleResource($r)),
            __('roles.roles_listed'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('admin:read');

        $role = Role::with('permissions')->find($id);
        if (! $role) {
            return $this->notFoundResponse(__('roles.role_not_found'));
        }

        return $this->successResponse(new RoleResource($role), __('roles.role_retrieved'));
    }

    public function showByName(string $name): JsonResponse
    {
        $this->authorize('admin:read');

        $role = Role::with('permissions')->where('name', $name)->first();
        if (! $role) {
            return $this->notFoundResponse(__('roles.role_not_found'));
        }

        return $this->successResponse(new RoleResource($role), __('roles.role_retrieved'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('admin:create');

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'required|array',
            'display_name.en' => 'required|string',
            'description' => 'nullable|array',
            'is_system' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => json_encode($validated['display_name']),
            'description' => isset($validated['description']) ? json_encode($validated['description']) : null,
            'is_system' => $validated['is_system'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        $role->load('permissions');

        return $this->createdResponse(new RoleResource($role), __('roles.role_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('admin:manage');

        $role = Role::find($id);
        if (! $role) {
            return $this->notFoundResponse(__('roles.role_not_found'));
        }

        $validated = $request->validate([
            'name' => "sometimes|string|unique:roles,name,{$id}",
            'display_name' => 'sometimes|array',
            'display_name.en' => 'sometimes|string',
            'description' => 'nullable|array',
            'is_system' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        // System roles cannot have name or is_system modified
        if ($role->is_system) {
            unset($validated['name'], $validated['is_system']);
        }

        // JSON-encode array fields for Spatie model
        if (isset($validated['display_name'])) {
            $validated['display_name'] = json_encode($validated['display_name']);
        }
        if (isset($validated['description'])) {
            $validated['description'] = json_encode($validated['description']);
        }

        $role->update($validated);

        $role->load('permissions');

        return $this->successResponse(new RoleResource($role->fresh('permissions')), __('roles.role_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('admin:manage');

        $role = Role::find($id);
        if (! $role) {
            return $this->notFoundResponse(__('roles.role_not_found'));
        }

        if ($role->is_system) {
            return $this->errorResponse(__('roles.system_role_delete'), 403);
        }

        $role->delete();

        return $this->successResponse(null, __('roles.role_deleted'));
    }

    public function permissions(int $id): JsonResponse
    {
        $this->authorize('admin:read');

        $role = Role::with('permissions')->find($id);
        if (! $role) {
            return $this->notFoundResponse(__('roles.role_not_found'));
        }

        return $this->successResponse(
            PermissionResource::collection($role->permissions),
            __('roles.role_permissions_listed'),
        );
    }

    public function setPermissions(Request $request, int $id): JsonResponse
    {
        $this->authorize('admin:manage');

        $role = Role::find($id);
        if (! $role) {
            return $this->notFoundResponse(__('roles.role_not_found'));
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $validated['permissions'])->get();
        $role->syncPermissions($permissions);

        $role->load('permissions');

        return $this->successResponse(new RoleResource($role), __('roles.permissions_set'));
    }

    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $this->authorize('admin:manage');

        $role = Role::find($id);
        if (! $role) {
            return $this->notFoundResponse(__('roles.role_not_found'));
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $validated['permissions'])->get();
        $role->givePermissionTo($permissions);

        $role->load('permissions');

        return $this->successResponse(new RoleResource($role), __('roles.permissions_assigned'));
    }

    public function revokePermissions(Request $request, int $id): JsonResponse
    {
        $this->authorize('admin:manage');

        $role = Role::find($id);
        if (! $role) {
            return $this->notFoundResponse(__('roles.role_not_found'));
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $validated['permissions'])->get();
        foreach ($permissions as $permission) {
            $role->revokePermissionTo($permission);
        }

        $role->load('permissions');

        return $this->successResponse(new RoleResource($role), __('roles.permissions_revoked'));
    }
}
