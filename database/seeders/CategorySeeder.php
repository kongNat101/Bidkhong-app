<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Electronics' => [
                'Smartphones & Tablets',
                'Computers & Laptops',
                'Cameras & Photography',
                'Audio & Headphones',
                'Gaming & Consoles',
                'Wearables & Smartwatch',
            ],
            'Fashion' => [
                "Men's Clothing",
                "Women's Clothing",
                'Shoes & Footwear',
                'Bags & Accessories',
                'Watches & Jewelry',
            ],
            'Collectibles' => [
                'Art & Paintings',
                'Toys & Figures',
                'Coins & Stamps',
                'Trading Cards',
                'Antiques & Vintage',
            ],
            'Home' => [
                'Furniture',
                'Home Decor',
                'Kitchen & Dining',
                'Garden & Outdoor',
            ],
            'Vehicles' => [
                'Cars',
                'Motorcycles',
                'Parts & Accessories',
                'Electric Vehicles',
            ],
            'Others' => [
                'Books & Magazines',
                'Sports & Fitness',
                'Musical Instruments',
                'Pet Supplies',
            ],
        ];

        foreach ($data as $categoryName => $subcategories) {
            $category = Category::create([
                'name' => $categoryName,
                'description' => null,
            ]);

            foreach ($subcategories as $subcategoryName) {
                Subcategory::create([
                    'category_id' => $category->id,
                    'name' => $subcategoryName,
                    'description' => null,
                ]);
            }
        }
    }
}