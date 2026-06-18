<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Order;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create 20 products, each with 1-3 stock locations
        Product::factory(20)->create()->each(function ($product) {
            $product->stocks()->createMany(
                \Database\Factories\StockFactory::new()
                    ->count(rand(1, 3))
                    ->make(['product_id' => $product->id])
                    ->toArray()
            );
        });

        // Create 15 orders
        Order::factory(15)->create();
    }
}