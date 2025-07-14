<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Electronics',
            'Clothing',
            'Books',
            'Food & Beverages',
            'Home & Garden'
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}