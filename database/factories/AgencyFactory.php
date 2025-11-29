<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Agency\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agency>
 */
class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'description' => json_encode([
                'en' => fake()->sentence(),
                'fr' => fake()->sentence(),
            ]),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'contact_person' => fake()->name(),
            'address' => fake()->streetAddress(),
            'city_id' => CityFactory::new(),
            'canton_id' => fn (array $attrs) => \App\Domain\Location\Models\City::find($attrs['city_id'])?->canton_id ?? CantonFactory::new(),
            'postal_code' => fake()->postcode(),
            'status' => 'active',
            'is_verified' => true,
        ];
    }
}
