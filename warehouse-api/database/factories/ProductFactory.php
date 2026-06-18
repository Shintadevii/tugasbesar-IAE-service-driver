<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku'         => 'WH-' . $this->faker->unique()->numberBetween(1000, 9999),
            'name'        => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->randomFloat(2, 1, 500),
            'category'    => $this->faker->randomElement(['packaging', 'electronics', 'furniture', 'tools', 'consumables']),
        ];
    }
}