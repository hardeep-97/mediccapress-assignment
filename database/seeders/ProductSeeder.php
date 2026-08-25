<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::query()->upsert([
            ['name' => 'Mechanical Keyboard', 'price' => 79.99, 'stock_quantity' => 25],
            ['name' => 'Wireless Mouse', 'price' => 34.50, 'stock_quantity' => 40],
            ['name' => 'USB-C Hub', 'price' => 49.00, 'stock_quantity' => 15],
            ['name' => 'Laptop Stand', 'price' => 42.75, 'stock_quantity' => 0],
        ], ['name'], ['price', 'stock_quantity']);
    }
}
