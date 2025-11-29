<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = json_decode(
            file_get_contents(base_path('data/roles.json')),
            true,
        );

        $rolePermissions = json_decode(
            file_get_contents(base_path('data/role_permissions.json')),
            true,
        );

        $now = now();

        // Insert roles
        $mongoToName = [];
        foreach ($roles as $role) {
            DB::table('roles')->insert([
                'name' => $role['name'],
                'display_name' => json_encode($role['display_name']),
                'description' => json_encode($role['description']),
                'is_system' => $role['is_system'],
                'is_active' => $role['is_active'],
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $mongoToName[$role['_id']] = $role['name'];
        }

        $this->command->info('Seeded ' . count($roles) . ' roles.');

        // Build lookup maps for role_has_permissions pivot
        $dbRoles = DB::table('roles')->pluck('id', 'name')->toArray();
        $dbPermissions = DB::table('permissions')->pluck('id', 'name')->toArray();

        // Build permission MongoDB _id → name map
        $permissionsData = json_decode(
            file_get_contents(base_path('data/permissions.json')),
            true,
        );
        $permMongoToName = [];
        foreach ($permissionsData as $perm) {
            $permMongoToName[$perm['_id']] = $perm['name'];
        }

        // Assign permissions to roles
        $pivotCount = 0;
        foreach ($rolePermissions as $rp) {
            $roleName = $mongoToName[$rp['role_id']] ?? null;
            $permName = $permMongoToName[$rp['permission_id']] ?? null;

            if ($roleName === null || $permName === null) {
                continue;
            }

            $roleId = $dbRoles[$roleName] ?? null;
            $permId = $dbPermissions[$permName] ?? null;

            if ($roleId === null || $permId === null) {
                continue;
            }

            DB::table('role_has_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permId,
            ]);
            $pivotCount++;
        }

        $this->command->info("Assigned {$pivotCount} role-permission mappings.");
    }
}
