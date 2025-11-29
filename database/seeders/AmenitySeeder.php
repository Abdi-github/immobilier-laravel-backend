<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = json_decode(
            file_get_contents(base_path('data/amenities.json')),
            true,
        );

        $now = now();

        foreach ($amenities as $amenity) {
            DB::table('amenities')->insert([
                'name' => json_encode($amenity['name']),
                'group' => $amenity['group'],
                'icon' => $amenity['icon'] ?? null,
                'sort_order' => $amenity['sort_order'] ?? 0,
                'is_active' => $amenity['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Seeded ' . count($amenities) . ' amenities.');
    }
}
