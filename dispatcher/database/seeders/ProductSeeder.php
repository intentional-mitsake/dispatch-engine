<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder; 
use App\Models\Products;


class ProductSeeder extends Seeder
{
    public function run(): void
    {
        for($i = 1; $i <= 10; $i++) {
            Products::create([
                'name' => "Product $i",
                'stock' => rand(400, 1000), // random stock between 600 and 1000
                'price' => rand(100, 1000) / 10, // random price between 10.0 and 100.0
            ]);
        }
    }
}
