<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $properties = json_decode(
            file_get_contents(base_path('data/properties.json')),
            true,
        );

        $cantonMap = $this->buildCantonMap();
        $cityMap = $this->buildCityMap();
        $categoryMap = $this->buildCategoryMap();
        $agencyMap = $this->buildAgencyMap();
        $amenityMap = $this->buildAmenityMap();

        $now = now();
        $seeded = 0;
        $amenityPivots = [];

        foreach ($properties as $property) {
            $cantonId = $cantonMap[$property['canton_id']] ?? null;
            $cityId = $cityMap[$property['city_id']] ?? null;
            $categoryId = $categoryMap[$property['category_id']] ?? null;
            $agencyId = $agencyMap[$property['agency_id']] ?? null;

            if ($cantonId === null || $cityId === null || $categoryId === null) {
                $this->command->warn("Skipping property {$property['property_id']}: missing FK mapping.");
                continue;
            }

            // Parse price — can be a number or string like "Prix sur demande"
            $price = $this->parsePrice($property['price'] ?? null);

            // Parse rooms — string like "5.5" or null
            $rooms = $this->parseNumeric($property['rooms'] ?? null);

            // Parse surface — string like "198 m²" or null
            $surface = $this->parseSurface($property['surface'] ?? null);

            // Build address from addresses array
            $address = $this->buildAddress($property['addresses'] ?? []);

            $propertyId = DB::table('properties')->insertGetId([
                'external_id' => $property['property_id'],
                'external_url' => $property['listing_url'] ?? null,
                'source_language' => $property['language'] ?? 'en',
                'category_id' => $categoryId,
                'agency_id' => $agencyId,
                'owner_id' => null,
                'transaction_type' => $property['transaction_type'] ?? 'rent',
                'price' => $price,
                'currency' => $property['currency'] ?? 'CHF',
                'additional_costs' => null,
                'rooms' => $rooms,
                'surface' => $surface,
                'address' => $address,
                'city_id' => $cityId,
                'canton_id' => $cantonId,
                'postal_code' => null,
                'proximity' => !empty($property['proximity'])
                    ? json_encode($property['proximity'])
                    : null,
                'status' => 'PUBLISHED',
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Collect amenity pivots
            if (!empty($property['amenity_id']) && is_array($property['amenity_id'])) {
                foreach ($property['amenity_id'] as $amenityMongoId) {
                    $amenityId = $amenityMap[$amenityMongoId] ?? null;
                    if ($amenityId !== null) {
                        $amenityPivots[] = [
                            'property_id' => $propertyId,
                            'amenity_id' => $amenityId,
                        ];
                    }
                }
            }

            $seeded++;
        }

        // Batch insert amenity pivots
        if (!empty($amenityPivots)) {
            foreach (array_chunk($amenityPivots, 500) as $chunk) {
                DB::table('property_amenity')->insert($chunk);
            }
        }

        $this->command->info("Seeded {$seeded} properties with " . count($amenityPivots) . ' amenity assignments.');
    }

    private function parsePrice(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Try to extract number from string like "CHF 1'200.00" or "1,200,000"
        $cleaned = preg_replace('/[^0-9.]/', '', str_replace(["'", ','], ['', ''], (string) $value));

        return $cleaned !== '' ? (float) $cleaned : 0;
    }

    private function parseNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = preg_replace('/[^0-9.]/', '', (string) $value);

        return $cleaned !== '' ? (float) $cleaned : null;
    }

    private function parseSurface(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Remove "m²" and any other non-numeric chars
        $cleaned = preg_replace('/[^0-9.]/', '', str_replace(["'", ','], ['', ''], (string) $value));

        return $cleaned !== '' ? (float) $cleaned : null;
    }

    private function buildAddress(array $addresses): string
    {
        if (empty($addresses)) {
            return 'N/A';
        }

        // Join address parts, filter out empty strings
        return implode(', ', array_filter($addresses, fn ($a) => trim((string) $a) !== ''));
    }

    private function buildCantonMap(): array
    {
        $cantons = json_decode(file_get_contents(base_path('data/cantons.json')), true);
        $dbCantons = DB::table('cantons')->pluck('id', 'code')->toArray();

        $map = [];
        foreach ($cantons as $canton) {
            $map[$canton['_id']] = $dbCantons[$canton['code']] ?? null;
        }

        return $map;
    }

    private function buildCityMap(): array
    {
        $cities = json_decode(file_get_contents(base_path('data/cities.json')), true);
        $cantonMap = $this->buildCantonMap();

        $dbCities = DB::table('cities')->get(['id', 'canton_id', 'postal_code']);
        $cityLookup = [];
        foreach ($dbCities as $dbCity) {
            $key = $dbCity->canton_id . ':' . $dbCity->postal_code;
            $cityLookup[$key] = $dbCity->id;
        }

        $map = [];
        foreach ($cities as $city) {
            $pgCantonId = $cantonMap[$city['canton_id']] ?? null;
            if ($pgCantonId !== null) {
                $key = $pgCantonId . ':' . $city['postal_code'];
                $map[$city['_id']] = $cityLookup[$key] ?? null;
            }
        }

        return $map;
    }

    private function buildCategoryMap(): array
    {
        $categories = json_decode(file_get_contents(base_path('data/categories.json')), true);
        $dbCategories = DB::table('categories')->pluck('id', 'slug')->toArray();

        $map = [];
        foreach ($categories as $category) {
            $map[$category['_id']] = $dbCategories[$category['slug']] ?? null;
        }

        return $map;
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

    private function buildAmenityMap(): array
    {
        $amenities = json_decode(file_get_contents(base_path('data/amenities.json')), true);

        // Match by icon since it's the most unique identifier
        $dbAmenities = DB::table('amenities')->pluck('id', 'icon')->toArray();

        $map = [];
        foreach ($amenities as $amenity) {
            $icon = $amenity['icon'] ?? null;
            if ($icon !== null) {
                $map[$amenity['_id']] = $dbAmenities[$icon] ?? null;
            }
        }

        return $map;
    }
}
