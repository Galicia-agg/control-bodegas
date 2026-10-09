<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('PRD-####??')),
            'name' => ucfirst(fake()->word()).' '.fake()->word(),
            'unit' => 'unidad',
            'cost' => fake()->randomFloat(2, 1, 500),
            'min_stock' => fake()->numberBetween(5, 30),
            'is_active' => true,
        ];
    }
}
