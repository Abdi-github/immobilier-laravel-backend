<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PropertyTranslationSeeder extends Seeder
{
    public function run(): void
    {
        $translations = json_decode(
            file_get_contents(base_path('data/property_translations.json')),
            true,
        );

        $propertyMap = $this->buildPropertyMap();

        $now = now();
        $batch = [];
        $seeded = 0;

        foreach ($translations as $translation) {
            $propertyId = $propertyMap[$translation['property_id']] ?? null;

            if ($propertyId === null) {
                continue;
            }

            $batch[] = [
                'property_id' => $propertyId,
                'language' => $translation['language'],
                'title' => $translation['title'],
                'description' => $translation['description'],
                'source' => $translation['source'] ?? 'original',
                'quality_score' => $translation['quality_score'] ?? null,
                'approval_status' => $translation['approval_status'] ?? 'PENDING',
                'created_at' => $translation['created_at'] ?? $now,
                'updated_at' => $translation['updated_at'] ?? $now,
            ];

            $seeded++;

            if (count($batch) >= 500) {
                DB::table('property_translations')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('property_translations')->insert($batch);
        }

        $this->command->info("Seeded {$seeded} property translations.");
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
