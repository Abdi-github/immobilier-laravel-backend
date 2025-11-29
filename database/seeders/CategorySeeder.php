<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = json_decode(
            file_get_contents(base_path('data/categories.json')),
            true,
        );

        $now = now();

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'section' => $category['section'],
                'name' => json_encode($category['name']),
                'slug' => $category['slug'],
                'icon' => $category['icon'] ?? null,
                'sort_order' => $category['sort_order'] ?? 0,
                'is_active' => $category['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Seeded ' . count($categories) . ' categories.');
    }
}
