<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Location\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'canton_id' => CantonFactory::new(),
            'name' => json_encode([
                'en' => fake()->city(),
                'fr' => fake()->city(),
                'de' => fake()->city(),
                'it' => fake()->city(),
            ]),
            'postal_code' => fake()->postcode(),
            'is_active' => true,
        ];
    }
}
