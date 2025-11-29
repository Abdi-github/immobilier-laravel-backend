<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'section' => fake()->randomElement(['residential', 'commercial']),
            'name' => json_encode([
                'en' => fake()->word() . ' Category',
                'fr' => 'Catégorie ' . fake()->word(),
                'de' => fake()->word() . ' Kategorie',
                'it' => 'Categoria ' . fake()->word(),
            ]),
            'slug' => fake()->unique()->slug(2),
            'icon' => 'home',
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
