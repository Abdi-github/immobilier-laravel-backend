<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Enums\TransactionType;
use App\Domain\Property\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'external_id' => fake()->unique()->uuid(),
            'source_language' => 'en',
            'category_id' => CategoryFactory::new(),
            'agency_id' => AgencyFactory::new(),
            'owner_id' => UserFactory::new()->agent(),
            'transaction_type' => fake()->randomElement([TransactionType::RENT->value, TransactionType::BUY->value]),
            'price' => fake()->randomFloat(2, 500, 5000000),
            'currency' => 'CHF',
            'rooms' => fake()->randomFloat(1, 1, 10),
            'surface' => fake()->randomFloat(2, 20, 500),
            'address' => fake()->streetAddress(),
            'city_id' => fn (array $attrs) => \App\Domain\Agency\Models\Agency::find($attrs['agency_id'])?->city_id ?? CityFactory::new(),
            'canton_id' => fn (array $attrs) => \App\Domain\Agency\Models\Agency::find($attrs['agency_id'])?->canton_id ?? CantonFactory::new(),
            'postal_code' => fake()->postcode(),
            'status' => PropertyStatus::PUBLISHED->value,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PropertyStatus::DRAFT->value,
            'published_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => PropertyStatus::PENDING_APPROVAL->value,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PropertyStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);
    }

    public function forRent(): static
    {
        return $this->state(fn () => [
            'transaction_type' => TransactionType::RENT->value,
        ]);
    }

    public function forSale(): static
    {
        return $this->state(fn () => [
            'transaction_type' => TransactionType::BUY->value,
        ]);
    }
}
