<?php

declare(strict_types=1);

namespace Tests\Traits;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait SeedsRolesAndPermissions
{
    protected function seedRolesAndPermissions(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the wildcard permission for super_admin
        $wildcard = Permission::findOrCreate('*', 'web');

        // Create core permissions needed for tests
        $permissionNames = [
            'users:read', 'users:create', 'users:delete', 'users:manage',
            'properties:read', 'properties:create', 'properties:update', 'properties:delete', 'properties:manage',
            'agencies:read', 'agencies:create', 'agencies:update', 'agencies:delete', 'agencies:manage',
            'leads:read', 'leads:create', 'leads:update', 'leads:delete', 'leads:manage',
            'categories:read', 'categories:create', 'categories:update', 'categories:delete', 'categories:manage',
            'amenities:read', 'amenities:create', 'amenities:update', 'amenities:delete', 'amenities:manage',
            'locations:read', 'locations:create', 'locations:update', 'locations:delete', 'locations:manage',
            'roles:read', 'roles:create', 'roles:update', 'roles:delete', 'roles:manage',
            'permissions:read', 'permissions:manage',
            'analytics:read',
        ];

        foreach ($permissionNames as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Create roles
        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->givePermissionTo('*');

        $platformAdmin = Role::findOrCreate('platform_admin', 'web');
        $platformAdmin->givePermissionTo(array_filter($permissionNames, fn ($p) => $p !== '*'));

        $agencyAdmin = Role::findOrCreate('agency_admin', 'web');
        $agencyAdmin->givePermissionTo([
            'properties:read', 'properties:create', 'properties:update', 'properties:delete',
            'leads:read', 'leads:create', 'leads:update', 'leads:manage',
        ]);

        $agent = Role::findOrCreate('agent', 'web');
        $agent->givePermissionTo([
            'properties:read', 'properties:create', 'properties:update', 'properties:delete',
            'leads:read', 'leads:update',
        ]);

        $owner = Role::findOrCreate('owner', 'web');
        $owner->givePermissionTo(['properties:read', 'properties:create', 'properties:update']);

        $endUser = Role::findOrCreate('end_user', 'web');
        $endUser->givePermissionTo(['properties:read']);
    }
}
