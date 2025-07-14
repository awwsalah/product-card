<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            ['name' => 'Laptop Dell XPS 13', 'sku' => 'DELL-XPS-001', 'category' => 'Electronics', 'quantity' => 15],
            ['name' => 'iPhone 14 Pro', 'sku' => 'APPL-IP14-001', 'category' => 'Electronics', 'quantity' => 8],
            ['name' => 'Samsung TV 55"', 'sku' => 'SAMS-TV55-001', 'category' => 'Electronics', 'quantity' => 5],
            ['name' => 'T-Shirt Blue L', 'sku' => 'TSH-BLU-L-001', 'category' => 'Clothing', 'quantity' => 50],
            ['name' => 'Jeans Black 32', 'sku' => 'JNS-BLK-32-001', 'category' => 'Clothing', 'quantity' => 30],
            ['name' => 'Laravel Book', 'sku' => 'BOOK-LAR-001', 'category' => 'Books', 'quantity' => 20],
            ['name' => 'PHP Cookbook', 'sku' => 'BOOK-PHP-001', 'category' => 'Books', 'quantity' => 12],
            ['name' => 'Coffee Beans 1kg', 'sku' => 'COFF-BEAN-001', 'category' => 'Food & Beverages', 'quantity' => 25],
            ['name' => 'Green Tea Box', 'sku' => 'TEA-GRN-001', 'category' => 'Food & Beverages', 'quantity' => 40],
            ['name' => 'Garden Chair', 'sku' => 'GARD-CHR-001', 'category' => 'Home & Garden', 'quantity' => 18],
        ];

        foreach ($products as $product) {
            $category = Category::where('name', $product['category'])->first();
            
            Product::create([
                'name' => $product['name'],
                'sku' => $product['sku'],
                'category_id' => $category->id,
                'quantity' => $product['quantity']
            ]);
        }
    }
}