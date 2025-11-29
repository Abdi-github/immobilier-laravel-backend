<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CantonSeeder extends Seeder
{
    public function run(): void
    {
        $cantons = json_decode(
            file_get_contents(base_path('data/cantons.json')),
            true,
        );

        $now = now();

        foreach ($cantons as $canton) {
            DB::table('cantons')->insert([
                'code' => $canton['code'],
                'name' => json_encode($canton['name']),
                'is_active' => $canton['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Seeded ' . count($cantons) . ' cantons.');
    }
}
