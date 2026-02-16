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
                'Wearables & Smartwatches',
            ],
            'Fashion' => [
                'Luxury Handbags',
                'Watches & Jewelry',
                'Designer Clothing',
                'Shoes & Sneakers',
                'Sunglasses & Eyewear',
                'Accessories & Belts',
            ],
            'Collectibles' => [
                'Action Figures & Toys',
                'Trading Cards',
                'Art & Paintings',
                'Comics & Manga',
                'Sports Memorabilia',
                'Antiques & Vintage',
            ],
            'Home' => [
                'Luxury Properties',
                'Condos & Apartments',
                'Houses & Villas',
                'Furniture',
                'Home Decor',
                'Appliances',
            ],
            'Vehicles' => [
                'Luxury Cars',
                'SUVs & Trucks',
                'Motorcycles',
                'Electric Vehicles',
                'Boats & Marine',
                'Classic Cars',
            ],
            'Others' => [
                'Sports & Fitness',
                'Musical Instruments',
                'Books & Media',
                'Beauty & Health',
                'Pet Supplies',
                'Services & Experiences',
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