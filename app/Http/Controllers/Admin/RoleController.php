<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('roles:read');

        $roles = Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn ($role) => $this->roleToArray($role));

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function show(int $id): Response
    {
        $this->authorize('roles:read');

        $role = Role::with('permissions')->findOrFail($id);

        $allPermissions = Permission::where('is_active', true)
            ->orderBy('resource')
            ->orderBy('action')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'display_name' => is_string($p->display_name) ? json_decode($p->display_name, true) : $p->display_name,
                'description' => is_string($p->description) ? json_decode($p->description, true) : $p->description,
                'resource' => $p->resource,
                'action' => $p->action,
            ]);

        $grouped = $allPermissions->groupBy('resource');

        return Inertia::render('Roles/Show', [
            'role' => $this->roleToArray($role),
            'allPermissions' => $allPermissions,
            'groupedPermissions' => $grouped,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('roles:create');

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'display_name' => 'nullable|array',
            'description' => 'nullable|array',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'display_name' => $validated['display_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_system' => false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('admin.roles.show', $role->id)
            ->with('success', 'Role created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorize('roles:manage');

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'display_name' => 'nullable|array',
            'description' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Don't allow changing name/is_system of system roles
        if (!$role->is_system) {
            $request->validate(['name' => 'nullable|string|max:100|unique:roles,name,' . $role->id]);
            if ($request->has('name')) {
                $validated['name'] = $request->input('name');
            }
        }

        $role->update($validated);

        return redirect()->back()->with('success', 'Role updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->authorize('roles:manage');

        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return redirect()->back()->with('error', 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }

    public function syncPermissions(Request $request, int $id): RedirectResponse
    {
        $this->authorize('roles:manage');

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $validated['permissions'])->get();
        $role->syncPermissions($permissions);

        return redirect()->back()->with('success', 'Permissions updated.');
    }

    private function roleToArray(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'display_name' => is_string($role->display_name) ? json_decode($role->display_name, true) : $role->display_name,
            'description' => is_string($role->description) ? json_decode($role->description, true) : $role->description,
            'is_system' => (bool) $role->is_system,
            'is_active' => (bool) $role->is_active,
            'permissions_count' => $role->permissions_count ?? $role->permissions?->count() ?? 0,
            'permissions' => $role->relationLoaded('permissions')
                ? $role->permissions->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'display_name' => is_string($p->display_name) ? json_decode($p->display_name, true) : $p->display_name,
                    'resource' => $p->resource,
                    'action' => $p->action,
                ])->toArray()
                : [],
            'created_at' => $role->created_at?->toISOString(),
            'updated_at' => $role->updated_at?->toISOString(),
        ];
    }
}
