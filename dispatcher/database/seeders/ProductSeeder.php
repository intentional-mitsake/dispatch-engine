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
                'stock' => rand(60, 140), // random stock between 60 and 140
                'price' => rand(100, 1000) / 10, // random price between 10.0 and 100.0
            ]);
        }
    }
}
