<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = json_decode(
            file_get_contents(base_path('data/permissions.json')),
            true,
        );

        $now = now();

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'name' => $permission['name'],
                'display_name' => json_encode($permission['display_name']),
                'description' => json_encode($permission['description']),
                'resource' => $permission['resource'],
                'action' => $permission['action'],
                'is_active' => $permission['is_active'],
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Seeded ' . count($permissions) . ' permissions.');
    }
}
