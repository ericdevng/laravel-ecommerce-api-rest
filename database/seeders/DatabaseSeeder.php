<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // === Usuarios ===
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        User::factory(5)->create();

        // categorias
        $categories = collect([
            'Electronics',
            'Clothing',
            'Home & Kitchen',
            'Books',
            'Sports & Outdoors',
            'Toys & Games',
            'Health & Beauty',
            'Automotive',
            'Garden & Outdoor',
            'Office Supplies',
            'Pet Supplies',
            'Grocery',
        ])->mapWithKeys(fn (string $name) => [
            $name => Category::create(['name' => $name])->id,
        ]);

        // 50 productos por categoria, con precios y stock variados
        $productData = [
            'Electronics' => [
                ['name' => 'Laptop Pro 15"', 'price' => 1299.99, 'stock' => 25],
                ['name' => 'Wireless Mouse', 'price' => 29.99, 'stock' => 150],
                ['name' => 'Mechanical Keyboard', 'price' => 89.99, 'stock' => 75],
                ['name' => 'USB-C Hub Adapter', 'price' => 45.99, 'stock' => 200],
                ['name' => '27" 4K Monitor', 'price' => 449.99, 'stock' => 30],
                ['name' => 'Noise Cancelling Headphones', 'price' => 199.99, 'stock' => 60],
                ['name' => 'Webcam HD 1080p', 'price' => 59.99, 'stock' => 100],
            ],
            'Clothing' => [
                ['name' => 'Classic T-Shirt', 'price' => 19.99, 'stock' => 300],
                ['name' => 'Denim Jacket', 'price' => 79.99, 'stock' => 50],
                ['name' => 'Running Sneakers', 'price' => 119.99, 'stock' => 80],
                ['name' => 'Winter Coat', 'price' => 149.99, 'stock' => 40],
                ['name' => 'Cotton Hoodie', 'price' => 49.99, 'stock' => 120],
            ],
            'Home & Kitchen' => [
                ['name' => 'Coffee Maker 12-Cup', 'price' => 69.99, 'stock' => 45],
                ['name' => 'Non-Stick Cookware Set', 'price' => 129.99, 'stock' => 35],
                ['name' => 'Robot Vacuum', 'price' => 299.99, 'stock' => 20],
                ['name' => 'Air Purifier', 'price' => 179.99, 'stock' => 25],
                ['name' => 'LED Desk Lamp', 'price' => 34.99, 'stock' => 90],
            ],
            'Books' => [
                ['name' => 'Clean Code', 'price' => 39.99, 'stock' => 200],
                ['name' => 'Design Patterns', 'price' => 44.99, 'stock' => 150],
                ['name' => 'The Pragmatic Programmer', 'price' => 42.99, 'stock' => 180],
                ['name' => 'Refactoring', 'price' => 49.99, 'stock' => 120],
                ['name' => 'Domain-Driven Design', 'price' => 54.99, 'stock' => 100],
            ],
            'Sports & Outdoors' => [
                ['name' => 'Yoga Mat', 'price' => 29.99, 'stock' => 200],
                ['name' => 'Dumbbells Set 20kg', 'price' => 89.99, 'stock' => 60],
                ['name' => 'Camping Tent 4-Person', 'price' => 199.99, 'stock' => 30],
                ['name' => 'Water Bottle 1L', 'price' => 14.99, 'stock' => 500],
                ['name' => 'Jump Rope', 'price' => 12.99, 'stock' => 300],
            ],
            'Toys & Games' => [
                ['name' => 'LEGO Classic Set', 'price' => 59.99, 'stock' => 80],
                ['name' => 'Board Game Night', 'price' => 34.99, 'stock' => 60],
                ['name' => 'RC Car Off-Road', 'price' => 79.99, 'stock' => 45],
                ['name' => 'Puzzle 1000 Pieces', 'price' => 19.99, 'stock' => 150],
                ['name' => 'Building Blocks 500pc', 'price' => 24.99, 'stock' => 100],
            ],
            'Health & Beauty' => [
                ['name' => 'Vitamin D3 Supplements', 'price' => 15.99, 'stock' => 400],
                ['name' => 'Electric Toothbrush', 'price' => 49.99, 'stock' => 120],
                ['name' => 'Moisturizer Cream', 'price' => 22.99, 'stock' => 250],
                ['name' => 'Protein Powder 1kg', 'price' => 39.99, 'stock' => 80],
                ['name' => 'Sunscreen SPF50', 'price' => 12.99, 'stock' => 300],
            ],
            'Automotive' => [
                ['name' => 'Car Phone Mount', 'price' => 19.99, 'stock' => 200],
                ['name' => 'Dash Cam 4K', 'price' => 99.99, 'stock' => 40],
                ['name' => 'Tire Pressure Gauge', 'price' => 9.99, 'stock' => 500],
                ['name' => 'Car Air Freshener 12pk', 'price' => 8.99, 'stock' => 600],
                ['name' => 'Jump Starter Pack', 'price' => 69.99, 'stock' => 50],
            ],
            'Garden & Outdoor' => [
                ['name' => 'Garden Tool Set 5pc', 'price' => 34.99, 'stock' => 80],
                ['name' => 'Solar Path Lights 10pk', 'price' => 29.99, 'stock' => 100],
                ['name' => 'Watering Can 2Gal', 'price' => 14.99, 'stock' => 200],
                ['name' => 'Raised Garden Bed', 'price' => 59.99, 'stock' => 40],
            ],
            'Office Supplies' => [
                ['name' => 'Printer Paper 500 sheets', 'price' => 7.99, 'stock' => 1000],
                ['name' => 'Ergonomic Office Chair', 'price' => 249.99, 'stock' => 25],
                ['name' => 'Whiteboard 36x24', 'price' => 39.99, 'stock' => 60],
                ['name' => 'Desk Organizer', 'price' => 24.99, 'stock' => 150],
                ['name' => 'Sticky Notes 12pk', 'price' => 9.99, 'stock' => 500],
            ],
            'Pet Supplies' => [
                ['name' => 'Dog Food 15kg', 'price' => 49.99, 'stock' => 100],
                ['name' => 'Cat Tree Tower', 'price' => 89.99, 'stock' => 30],
                ['name' => 'Pet Carrier Medium', 'price' => 39.99, 'stock' => 50],
                ['name' => 'Automatic Feeder', 'price' => 59.99, 'stock' => 40],
            ],
            'Grocery' => [
                ['name' => 'Organic Coffee Beans 1kg', 'price' => 18.99, 'stock' => 300],
                ['name' => 'Extra Virgin Olive Oil 1L', 'price' => 12.99, 'stock' => 400],
                ['name' => 'Mixed Nuts 500g', 'price' => 14.99, 'stock' => 250],
            ],
        ];

        foreach ($productData as $categoryName => $products) { // Se asume que todas las categorías existen en la base de datos
            $categoryId = $categories[$categoryName];
            foreach ($products as $product) {
                Product::create(array_merge($product, [
                    'category_id' => $categoryId,
                    'description' => fake()->paragraph(1),
                    'is_active' => true,
                ]));
            }
        }
    }
}
