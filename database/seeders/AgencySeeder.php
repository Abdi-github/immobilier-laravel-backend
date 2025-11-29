<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AgencySeeder extends Seeder
{
    public function run(): void
    {
        $agencies = json_decode(
            file_get_contents(base_path('data/agencies.json')),
            true,
        );

        $cantonMap = $this->buildCantonMap();
        $cityMap = $this->buildCityMap();

        $now = now();

        foreach ($agencies as $agency) {
            $cantonId = $cantonMap[$agency['canton_id']] ?? null;
            $cityId = $cityMap[$agency['city_id']] ?? null;

            if ($cantonId === null || $cityId === null) {
                $this->command->warn("Skipping agency {$agency['name']}: missing canton or city mapping.");
                continue;
            }

            DB::table('agencies')->insert([
                'name' => $agency['name'],
                'slug' => Str::slug($agency['name']),
                'description' => null,
                'logo_url' => $agency['logo'] ?? null,
                'website' => $agency['website'] ?? null,
                'email' => $agency['email'] ?? null,
                'phone' => $agency['phone'] ?? null,
                'contact_person' => $agency['contact_person'] ?? null,
                'address' => $agency['address'],
                'city_id' => $cityId,
                'canton_id' => $cantonId,
                'postal_code' => $agency['postal_code'] ?? null,
                'status' => 'active',
                'is_verified' => $agency['verified'] ?? false,
                'verification_date' => ($agency['verified'] ?? false) ? $now : null,
                'total_properties' => $agency['total_properties'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Seeded ' . count($agencies) . ' agencies.');
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

        // Build canton map first so we can match cities properly
        $cantonMap = $this->buildCantonMap();

        // Build city lookup by (canton_id, postal_code) since city names can repeat
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
}
