<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Product::create([
            'name' => 'Flash Sale Super Phone',
            'price' => 199.99,
            'stock' => 10,
        ]);

        \App\Models\Product::create([
            'name' => 'Regular Laptop',
            'price' => 999.99,
            'stock' => 50,
        ]);

        \App\Models\Product::create([
            'name' => 'Wireless Headphones',
            'price' => 49.99,
            'stock' => 100,
        ]);
    }
}
