<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Location\Models\Canton;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Canton>
 */
class CantonFactory extends Factory
{
    protected $model = Canton::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'name' => json_encode([
                'en' => fake()->city() . ' Canton',
                'fr' => 'Canton de ' . fake()->city(),
                'de' => 'Kanton ' . fake()->city(),
                'it' => 'Cantone di ' . fake()->city(),
            ]),
            'is_active' => true,
        ];
    }
}
