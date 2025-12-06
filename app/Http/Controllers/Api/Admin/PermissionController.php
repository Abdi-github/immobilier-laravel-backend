<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

final class PermissionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('admin:read');

        $query = Permission::query()
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'ILIKE', "%{$s}%")
                    ->orWhereRaw("display_name::text ILIKE ?", ["%{$s}%"]);
            }))
            ->when($request->query('resource'), fn ($q, $r) => $q->where('resource', $r))
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)));

        $sort = $request->query('sort', 'name');
        $order = $request->query('order', 'asc');
        $query->orderBy($sort, $order);

        $permissions = $query->paginate(
            (int) $request->query('limit', '50'),
            ['*'],
            'page',
            (int) $request->query('page', '1'),
        );

        return $this->paginatedResponse(
            $permissions->through(fn ($p) => new PermissionResource($p)),
            __('roles.permissions_listed'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('admin:read');

        $permission = Permission::find($id);
        if (! $permission) {
            return $this->notFoundResponse(__('roles.permission_not_found'));
        }

        return $this->successResponse(new PermissionResource($permission), __('roles.permission_retrieved'));
    }

    public function resources(): JsonResponse
    {
        $this->authorize('admin:read');

        $resources = Permission::query()
            ->select('resource')
            ->distinct()
            ->orderBy('resource')
            ->pluck('resource');

        return $this->successResponse($resources, __('roles.resources_listed'));
    }

    public function grouped(): JsonResponse
    {
        $this->authorize('admin:read');

        $permissions = Permission::query()
            ->orderBy('resource')
            ->orderBy('action')
            ->get();

        $grouped = $permissions->groupBy('resource')->map(fn ($group) => PermissionResource::collection($group));

        return $this->successResponse($grouped, __('roles.permissions_listed'));
    }

    public function active(): JsonResponse
    {
        $this->authorize('admin:read');

        $permissions = Permission::where('is_active', true)
            ->orderBy('resource')
            ->orderBy('action')
            ->get();

        return $this->successResponse(PermissionResource::collection($permissions), __('roles.active_permissions'));
    }

    public function showByName(string $name): JsonResponse
    {
        $this->authorize('admin:read');

        $permission = Permission::where('name', $name)->first();
        if (! $permission) {
            return $this->notFoundResponse(__('roles.permission_not_found'));
        }

        return $this->successResponse(new PermissionResource($permission), __('roles.permission_retrieved'));
    }

    public function byResource(string $resource): JsonResponse
    {
        $this->authorize('admin:read');

        $permissions = Permission::where('resource', $resource)
            ->orderBy('action')
            ->get();

        return $this->successResponse(PermissionResource::collection($permissions), __('roles.permissions_listed'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('admin:create');

        $validated = $request->validate([
            'name' => 'required|string|regex:/^[a-z_]+:[a-z_]+$/|unique:permissions,name',
            'display_name' => 'required|array',
            'display_name.en' => 'required|string',
            'description' => 'nullable|array',
            'resource' => 'required|string',
            'action' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'display_name' => json_encode($validated['display_name']),
            'description' => isset($validated['description']) ? json_encode($validated['description']) : null,
            'resource' => $validated['resource'],
            'action' => $validated['action'],
            'is_active' => $validated['is_active'] ?? true,
            'guard_name' => 'web',
        ]);

        return $this->createdResponse(new PermissionResource($permission), __('roles.permission_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('admin:manage');

        $permission = Permission::find($id);
        if (! $permission) {
            return $this->notFoundResponse(__('roles.permission_not_found'));
        }

        $validated = $request->validate([
            'name' => "sometimes|string|regex:/^[a-z_]+:[a-z_]+$/|unique:permissions,name,{$id}",
            'display_name' => 'sometimes|array',
            'display_name.en' => 'sometimes|string',
            'description' => 'nullable|array',
            'resource' => 'sometimes|string',
            'action' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        // JSON-encode array fields for Spatie model
        if (isset($validated['display_name'])) {
            $validated['display_name'] = json_encode($validated['display_name']);
        }
        if (isset($validated['description'])) {
            $validated['description'] = json_encode($validated['description']);
        }

        $permission->update($validated);

        return $this->successResponse(new PermissionResource($permission->fresh()), __('roles.permission_updated'));
    }

    public function softDelete(int $id): JsonResponse
    {
        $this->authorize('admin:manage');

        $permission = Permission::find($id);
        if (! $permission) {
            return $this->notFoundResponse(__('roles.permission_not_found'));
        }

        $permission->update(['is_active' => false]);

        return $this->successResponse(null, __('roles.permission_deactivated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('admin:manage');

        $permission = Permission::find($id);
        if (! $permission) {
            return $this->notFoundResponse(__('roles.permission_not_found'));
        }

        $permission->delete();

        return $this->successResponse(null, __('roles.permission_deleted'));
    }
}
