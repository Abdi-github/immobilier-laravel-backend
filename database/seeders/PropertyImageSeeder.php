<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PropertyImageSeeder extends Seeder
{
    public function run(): void
    {
        $images = json_decode(
            file_get_contents(base_path('data/property_images.json')),
            true,
        );

        $propertyMap = $this->buildPropertyMap();

        $now = now();
        $batch = [];
        $seeded = 0;

        foreach ($images as $image) {
            $propertyId = $propertyMap[$image['property_id']] ?? null;

            if ($propertyId === null) {
                continue;
            }

            $batch[] = [
                'property_id' => $propertyId,
                'url' => $image['url'],
                'secure_url' => $image['url'], // Use same URL — all are HTTPS
                'alt_text' => $image['alt_text'] ?? null,
                'sort_order' => $image['order'] ?? 0,
                'is_primary' => $image['is_primary'] ?? false,
                'source' => 'cloudinary',
                'original_url' => $image['url'],
                'created_at' => $image['created_at'] ?? $now,
                'updated_at' => $image['updated_at'] ?? $now,
            ];

            $seeded++;

            if (count($batch) >= 500) {
                DB::table('property_images')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('property_images')->insert($batch);
        }

        $this->command->info("Seeded {$seeded} property images.");
    }

    private function buildPropertyMap(): array
    {
        $properties = json_decode(file_get_contents(base_path('data/properties.json')), true);
        $dbProperties = DB::table('properties')->pluck('id', 'external_id')->toArray();

        $map = [];
        foreach ($properties as $property) {
            $map[$property['_id']] = $dbProperties[$property['property_id']] ?? null;
        }

        return $map;
    }
}
