<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $product  = Product::factory()->create();
        $quantity = $this->faker->numberBetween(1, 20);

        return [
            'reference'  => 'ORD-' . strtoupper(Str::random(8)),
            'status'     => $this->faker->randomElement(['pending','processing','shipped','completed','cancelled']),
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'total'      => $product->price * $quantity,
        ];
    }
}