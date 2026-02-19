<?php

namespace Database\Seeders;

use App\Models\Bid;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // สร้าง 3 users สำหรับเป็น seller และ bidder
        $users = [];
        $userNames = ['Seller Somchai', 'Bidder Somsri', 'Bidder Somkid'];
        foreach ($userNames as $i => $name) {
            $email = 'seed_user_' . ($i + 1) . '@example.com';
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::factory()->create([
                    'name' => $name,
                    'email' => $email,
                ]);
            }
            // สร้าง Wallet ถ้ายังไม่มี
            if (!$user->wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'balance_available' => 1000000,
                    'balance_total' => 1000000,
                    'balance_pending' => 0,
                    'withdraw' => 0,
                    'deposit' => 0,
                ]);
            }
            $users[] = $user;
        }

        // สินค้าทั้ง 18 ชิ้น: [category_name, subcategory_name, product_name, tag, starting_price, buyout_price, description]
        $products = [
            // Electronics
            ['Electronics', 'Smartphones & Tablets', 'iPhone 15 Pro Max 256GB', 'hot', 15000, 35000, 'สภาพดี 98% ใช้งานมา 3 เดือน ครบกล่อง ประกันศูนย์เหลือ 9 เดือน'],
            ['Electronics', 'Computers & Laptops', 'MacBook Air M3 ใหม่แกะกล่อง', 'incoming', 25000, 42000, 'MacBook Air M3 ชิป Apple Silicon ใหม่แกะกล่อง สี Midnight RAM 16GB SSD 512GB'],
            ['Electronics', 'Audio & Headphones', 'Sony WH-1000XM5 หูฟัง', 'ending', 3000, 9000, 'หูฟังตัดเสียงอันดับ 1 สภาพ 95% มีกล่อง อุปกรณ์ครบ'],

            // Fashion
            ['Fashion', 'Shoes & Footwear', 'Nike Air Jordan 1 Retro High', 'hot', 4000, 12000, 'Jordan 1 Retro High OG Chicago ไซส์ 42 ของแท้ 100% มีใบเสร็จ'],
            ['Fashion', 'Bags & Accessories', 'กระเป๋า Gucci Marmont Mini', 'incoming', 15000, 35000, 'กระเป๋า Gucci GG Marmont Mini สีดำ ของแท้ มีใบรับประกัน สภาพ 90%'],
            ['Fashion', 'Watches & Jewelry', 'นาฬิกา Casio G-Shock GA-2100', 'ending', 1500, 5000, 'G-Shock CasiOak GA-2100-1A1 สีดำ ของแท้ ประกัน CMG'],

            // Collectibles
            ['Collectibles', 'Art & Paintings', 'ภาพวาดสีน้ำมันวิวทะเล', 'hot', 8000, 25000, 'ภาพวาดสีน้ำมันบนผ้าใบ ขนาด 60x90 cm ฝีมือศิลปินไทย มีใบรับรอง'],
            ['Collectibles', 'Trading Cards', 'Pokemon Card Charizard Holo', 'incoming', 5000, 15000, 'การ์ดโปเกมอน Charizard Holo 1st Edition สภาพ PSA 8 หายากมาก'],
            ['Collectibles', 'Coins & Stamps', 'เหรียญ ร.5 หายาก ปี 2400', 'ending', 20000, 50000, 'เหรียญหนึ่งบาทรัชกาลที่ 5 ปี พ.ศ. 2400 สภาพสวย ผ่านการรับรอง'],

            // Home
            ['Home', 'Furniture', 'โซฟา L-Shape หนังแท้', 'hot', 12000, 30000, 'โซฟาตัว L หนังแท้ สีน้ำตาล ขนาด 3 ที่นั่ง สภาพดี ใช้งาน 1 ปี'],
            ['Home', 'Home Decor', 'โคมไฟ Nordic สไตล์มินิมอล', 'incoming', 800, 3000, 'โคมไฟตั้งพื้น สไตล์ Nordic ขาไม้ โป๊ะผ้า สูง 150 cm ของใหม่'],
            ['Home', 'Kitchen & Dining', 'ชุดจานชามเซรามิค 24 ชิ้น', 'ending', 1200, 4000, 'ชุดจานชามเซรามิคญี่ปุ่น 24 ชิ้น ลายดอกไม้ ใหม่ยังไม่แกะ'],

            // Vehicles
            ['Vehicles', 'Cars', 'Honda Civic FD ปี 2008', 'hot', 150000, 280000, 'Honda Civic FD 1.8 S ปี 2008 สีเทา เกียร์ออโต้ ไมล์ 150,000 km เจ้าของขายเอง'],
            ['Vehicles', 'Motorcycles', 'Ducati Monster 821 ปี 2020', 'incoming', 250000, 400000, 'Ducati Monster 821 ปี 2020 สีแดง ไมล์ 8,000 km ศูนย์ดูแล ประวัติชัดเจน'],
            ['Vehicles', 'Parts & Accessories', 'ล้อแม็ก TE37 18 นิ้ว ของแท้', 'ending', 25000, 55000, 'ล้อแม็ก Volk Racing TE37 ขอบ 18 นิ้ว 5 รู PCD 114.3 ของแท้ Made in Japan'],

            // Others
            ['Others', 'Books & Magazines', 'Harry Potter Box Set 7 เล่ม', 'hot', 800, 2500, 'Harry Potter ฉบับภาษาอังกฤษ ปกแข็ง ครบ 7 เล่ม สภาพดี'],
            ['Others', 'Musical Instruments', 'กีตาร์ Yamaha F310', 'incoming', 2500, 5500, 'กีตาร์โปร่ง Yamaha F310 สภาพ 90% เสียงดี แถมกระเป๋า คาโป้ สายสำรอง'],
            ['Others', 'Sports & Fitness', 'ลู่วิ่งไฟฟ้า Xiaomi WalkingPad', 'ending', 5000, 12000, 'ลู่วิ่งไฟฟ้า Xiaomi WalkingPad R1 Pro พับเก็บได้ ใช้งาน 6 เดือน'],
        ];

        // จำนวน bids ตาม tag
        $bidCounts = [
            'hot' => [12, 11, 10, 13, 15, 10],  // 6 Hot products
            'ending' => [3, 2, 2, 1, 3, 2],      // 6 Ending products
            'incoming' => [0, 0, 0, 0, 0, 0],    // 6 Incoming products (ไม่มี bids)
        ];

        $hotIndex = 0;
        $endingIndex = 0;
        $incomingIndex = 0;

        foreach ($products as $data) {
            [$categoryName, $subcategoryName, $name, $tag, $startingPrice, $buyoutPrice, $description] = $data;

            // ดึง category + subcategory จาก DB
            $category = Category::where('name', $categoryName)->first();
            $subcategory = Subcategory::where('name', $subcategoryName)
                ->where('category_id', $category->id)
                ->first();

            // กำหนดเจ้าของสินค้า (สลับ 3 users)
            $seller = $users[array_rand($users)];

            // กำหนดเวลาตาม tag
            switch ($tag) {
                case 'hot':
                    $createdAt = now()->subDays(3);
                    $auctionEnd = now()->addDays(4);
                    $numBids = $bidCounts['hot'][$hotIndex++];
                    break;
                case 'incoming':
                    $createdAt = now()->subMinutes(rand(10, 120)); // เพิ่งลง 10 นาที - 2 ชม. ก่อน
                    $auctionEnd = now()->addDays(5);
                    $numBids = $bidCounts['incoming'][$incomingIndex++];
                    break;
                case 'ending':
                    $createdAt = now()->subDays(4);
                    $auctionEnd = now()->addMinutes(rand(10, 50)); // เหลืออีก 10-50 นาที
                    $numBids = $bidCounts['ending'][$endingIndex++];
                    break;
            }

            // สร้าง Product
            $product = Product::create([
                'user_id' => $seller->id,
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'name' => $name,
                'description' => $description,
                'starting_price' => $startingPrice,
                'current_price' => $startingPrice,
                'min_price' => $startingPrice,
                'buyout_price' => $buyoutPrice,
                'auction_end_time' => $auctionEnd,
                'location' => 'กรุงเทพมหานคร',
                'status' => 'active',
            ]);

            // อัปเดต created_at (ต้องทำหลัง create เพราะ Laravel จะ override)
            $product->update(['created_at' => $createdAt]);

            // สร้าง Bids ถ้ามี
            if ($numBids > 0) {
                $this->createBids($product, $users, $seller, $numBids, $startingPrice, $buyoutPrice);
            }
        }
    }

    private function createBids(Product $product, array $users, User $seller, int $numBids, float $startingPrice, float $buyoutPrice): void
    {
        // คำนวณ bid increment ให้สมจริง (ใช้ส่วนต่างระหว่าง buyout กับ starting หาร จำนวน bids)
        $priceRange = $buyoutPrice - $startingPrice;
        $increment = max((int) ($priceRange / ($numBids + 5)), 1); // ให้ราคาไม่เกิน buyout

        // Bidders = users ที่ไม่ใช่ seller
        $bidders = array_values(array_filter($users, fn($u) => $u->id !== $seller->id));

        $currentBidPrice = $startingPrice;

        for ($i = 0; $i < $numBids; $i++) {
            $currentBidPrice += $increment;

            // สลับ bidder
            $bidder = $bidders[$i % count($bidders)];

            // สถานะ: bid สุดท้ายเป็น active, ที่เหลือเป็น outbid
            $status = ($i === $numBids - 1) ? 'active' : 'outbid';

            Bid::create([
                'user_id' => $bidder->id,
                'product_id' => $product->id,
                'price' => $currentBidPrice,
                'time' => $product->created_at->addHours($i + 1),
                'status' => $status,
            ]);
        }

        // อัปเดต current_price ตาม bid ล่าสุด
        $product->update(['current_price' => $currentBidPrice]);
    }
}
