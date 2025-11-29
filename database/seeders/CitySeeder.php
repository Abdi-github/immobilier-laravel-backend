<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = json_decode(
            file_get_contents(base_path('data/cities.json')),
            true,
        );

        // Build canton MongoDB _id → PG id lookup
        $cantonMap = $this->buildCantonMap();

        $now = now();
        $batch = [];

        foreach ($cities as $city) {
            $cantonId = $cantonMap[$city['canton_id']] ?? null;

            if ($cantonId === null) {
                $this->command->warn("Skipping city {$city['name']}: canton {$city['canton_id']} not found.");
                continue;
            }

            // City name is a plain string in MERN data — wrap in translated JSON
            $nameJson = json_encode([
                'en' => $city['name'],
                'fr' => $city['name'],
                'de' => $city['name'],
                'it' => $city['name'],
            ]);

            $batch[] = [
                'canton_id' => $cantonId,
                'name' => $nameJson,
                'postal_code' => $city['postal_code'],
                'image_url' => $city['image_url'] ?? null,
                'is_active' => $city['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Insert in batches of 500 for performance
            if (count($batch) >= 500) {
                DB::table('cities')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('cities')->insert($batch);
        }

        $this->command->info('Seeded ' . count($cities) . ' cities.');
    }

    private function buildCantonMap(): array
    {
        $cantons = json_decode(
            file_get_contents(base_path('data/cantons.json')),
            true,
        );

        $dbCantons = DB::table('cantons')->pluck('id', 'code')->toArray();

        $map = [];
        foreach ($cantons as $canton) {
            $map[$canton['_id']] = $dbCantons[$canton['code']] ?? null;
        }

        return $map;
    }
}
