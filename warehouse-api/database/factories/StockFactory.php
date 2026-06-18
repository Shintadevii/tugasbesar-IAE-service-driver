<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id'    => Product::factory(),
            'location'      => 'Rack ' . $this->faker->randomElement(['A','B','C','D']) . '-' . $this->faker->numberBetween(1, 20),
            'quantity'      => $this->faker->numberBetween(0, 500),
            'reorder_level' => $this->faker->numberBetween(5, 50),
        ];
    }
}