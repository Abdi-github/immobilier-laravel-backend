<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CantonSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            AmenitySeeder::class,
            AgencySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            PropertySeeder::class,
            PropertyImageSeeder::class,
            PropertyTranslationSeeder::class,
        ]);
    }
}
