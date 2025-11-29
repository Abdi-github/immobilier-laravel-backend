<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = json_decode(
            file_get_contents(base_path('data/users.json')),
            true,
        );

        $userRoles = json_decode(
            file_get_contents(base_path('data/user_roles.json')),
            true,
        );

        $agencyMap = $this->buildAgencyMap();

        $now = now();
        $mongoToEmail = [];

        foreach ($users as $user) {
            $agencyId = null;
            if (!empty($user['agency_id'])) {
                $agencyId = $agencyMap[$user['agency_id']] ?? null;
            }

            $status = ($user['is_active'] ?? false) ? 'active' : 'pending';

            DB::table('users')->insert([
                'email' => $user['email'],
                // Convert $2a$ (Node.js bcrypt) to $2y$ (PHP bcrypt) — algorithmically identical
                'password' => str_replace('$2a$', '$2y$', $user['password_hash']),
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'phone' => $user['phone'] ?? null,
                'avatar_url' => null,
                'user_type' => $user['user_type'],
                'agency_id' => $agencyId,
                'preferred_language' => $user['preferred_language'] ?? 'en',
                'notification_preferences' => null,
                'status' => $status,
                'email_verified_at' => ($user['is_verified'] ?? false)
                    ? ($user['verified_at'] ?? $now)
                    : null,
                'last_login_at' => null,
                'created_at' => $user['created_at'] ?? $now,
                'updated_at' => $user['updated_at'] ?? $now,
            ]);

            $mongoToEmail[$user['_id']] = $user['email'];
        }

        $this->command->info('Seeded ' . count($users) . ' users.');

        // Assign roles to users via model_has_roles (Spatie)
        $dbUsers = DB::table('users')->pluck('id', 'email')->toArray();

        $rolesData = json_decode(file_get_contents(base_path('data/roles.json')), true);
        $roleMongoToName = [];
        foreach ($rolesData as $role) {
            $roleMongoToName[$role['_id']] = $role['name'];
        }

        $dbRoles = DB::table('roles')->pluck('id', 'name')->toArray();

        $assignCount = 0;
        foreach ($userRoles as $ur) {
            $email = $mongoToEmail[$ur['user_id']] ?? null;
            $roleName = $roleMongoToName[$ur['role_id']] ?? null;

            if ($email === null || $roleName === null) {
                continue;
            }

            $userId = $dbUsers[$email] ?? null;
            $roleId = $dbRoles[$roleName] ?? null;

            if ($userId === null || $roleId === null) {
                continue;
            }

            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'App\\Domain\\User\\Models\\User',
                'model_id' => $userId,
            ]);
            $assignCount++;
        }

        $this->command->info("Assigned {$assignCount} user-role mappings.");
    }

    private function buildAgencyMap(): array
    {
        $agencies = json_decode(file_get_contents(base_path('data/agencies.json')), true);
        $dbAgencies = DB::table('agencies')->pluck('id', 'name')->toArray();

        $map = [];
        foreach ($agencies as $agency) {
            $map[$agency['_id']] = $dbAgencies[$agency['name']] ?? null;
        }

        return $map;
    }
}
