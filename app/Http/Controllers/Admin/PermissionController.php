<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

final class PermissionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('permissions:read');

        $permissions = Permission::query()
            ->orderBy('resource')
            ->orderBy('action')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'guard_name' => $p->guard_name,
                'display_name' => is_string($p->display_name) ? json_decode($p->display_name, true) : $p->display_name,
                'description' => is_string($p->description) ? json_decode($p->description, true) : $p->description,
                'resource' => $p->resource,
                'action' => $p->action,
                'is_active' => (bool) $p->is_active,
                'created_at' => $p->created_at?->toISOString(),
            ]);

        $grouped = $permissions->groupBy('resource');

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissions,
            'grouped' => $grouped,
        ]);
    }
}
