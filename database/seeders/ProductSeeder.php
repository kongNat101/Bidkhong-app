<?php

namespace Database\Seeders;

use App\Models\Bid;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // === สร้าง Sellers 3 คน ===
        $sellers = [];
        $sellerData = [
            ['Seller Somchai', 'seed_user_1@example.com', '0811111111'],
            ['Seller Somsri', 'seed_user_2@example.com', '0822222222'],
            ['Seller Somkid', 'seed_user_3@example.com', '0833333333'],
        ];
        foreach ($sellerData as [$name, $email, $phone]) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::factory()->create([
                    'name' => $name,
                    'email' => $email,
                    'phone_number' => $phone,
                ]);
            }
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
            $sellers[] = $user;
        }

        // === สร้าง Bidders 10 คน ===
        $bidders = [];
        for ($i = 1; $i <= 10; $i++) {
            $email = "bidder" . sprintf('%02d', $i) . "@test.com";
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::factory()->create([
                    'name' => "Bidder" . sprintf('%02d', $i),
                    'email' => $email,
                    'phone_number' => '09' . sprintf('%02d', $i) . '000' . sprintf('%04d', $i),
                ]);
            }
            if (!$user->wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'balance_available' => 5000000,
                    'balance_total' => 5000000,
                    'balance_pending' => 0,
                    'withdraw' => 0,
                    'deposit' => 0,
                ]);
            }
            $bidders[] = $user;
        }

        // สร้างโฟลเดอร์ products ถ้ายังไม่มี
        Storage::disk('public')->makeDirectory('products');

        // === สินค้าทั้งหมด ===
        // [category, subcategory, name, tag, starting_price, buyout_price, bid_increment, description, image_keyword, location]
        $products = [
            // ===== HOT (8 items — จะมี 10+ bids) =====
            ['Electronics', 'Smartphones & Tablets', 'iPhone 15 Pro Max 256GB', 'hot', 15000, 35000, 1000, 'สภาพดี 98% ใช้งานมา 3 เดือน ครบกล่อง ประกันศูนย์เหลือ 9 เดือน', 'iphone', 'กรุงเทพมหานคร'],
            ['Electronics', 'Computers & Laptops', 'MacBook Air M3 ใหม่แกะกล่อง', 'hot', 25000, 42000, 2000, 'MacBook Air M3 ชิป Apple Silicon สี Midnight RAM 16GB SSD 512GB', 'macbook+laptop', 'กรุงเทพมหานคร'],
            ['Fashion', 'Shoes & Footwear', 'Nike Air Jordan 1 Retro High', 'hot', 4000, 12000, 500, 'Jordan 1 Retro High OG Chicago ไซส์ 42 ของแท้ 100% มีใบเสร็จ', 'sneakers', 'เชียงใหม่'],
            ['Fashion', 'Bags & Accessories', 'กระเป๋า Gucci Marmont Mini', 'hot', 15000, 35000, 2000, 'กระเป๋า Gucci GG Marmont Mini สีดำ ของแท้ มีใบรับประกัน สภาพ 90%', 'luxury+handbag', 'กรุงเทพมหานคร'],
            ['Collectibles', 'Art & Paintings', 'ภาพวาดสีน้ำมันวิวทะเล', 'hot', 8000, 25000, 1000, 'ภาพวาดสีน้ำมันบนผ้าใบ ขนาด 60x90 cm ฝีมือศิลปินไทย มีใบรับรอง', 'oil+painting', 'กรุงเทพมหานคร'],
            ['Home', 'Furniture', 'โซฟา L-Shape หนังแท้', 'hot', 12000, 30000, 1500, 'โซฟาตัว L หนังแท้ สีน้ำตาล ขนาด 3 ที่นั่ง สภาพดี ใช้งาน 1 ปี', 'leather+sofa', 'นนทบุรี'],
            ['Vehicles', 'Cars', 'Honda Civic FD ปี 2008', 'hot', 150000, 280000, 5000, 'Honda Civic FD 1.8 S ปี 2008 สีเทา เกียร์ออโต้ ไมล์ 150,000 km', 'honda+civic', 'กรุงเทพมหานคร'],
            ['Others', 'Books & Magazines', 'Harry Potter Box Set 7 เล่ม', 'hot', 800, 2500, 100, 'Harry Potter ฉบับภาษาอังกฤษ ปกแข็ง ครบ 7 เล่ม สภาพดี', 'harry+potter+books', 'เชียงใหม่'],

            // ===== ENDING (3 items — จบภายใน 6 ชม.) =====
            ['Electronics', 'Audio & Headphones', 'Apple AirPods Pro 2 USB-C ของแท้', 'ending', 5000, 8000, 500, 'AirPods Pro 2 USB-C สภาพ 95% ใช้ 3 เดือน ANC ดีมาก มีกล่อง+สายชาร์จ', 'airpods', 'กรุงเทพมหานคร'],
            ['Fashion', 'Bags & Accessories', 'กระเป๋า Coach Tabby 26 สีดำ', 'ending', 8000, 15000, 1000, 'Coach Tabby Shoulder Bag 26 สีดำ หนังแท้ สภาพ 98% ใช้ 2 ครั้ง', 'coach+bag', 'เชียงใหม่'],
            ['Home', 'Kitchen & Dining', 'เครื่องดูดฝุ่น Dyson V12 Detect Slim', 'ending', 12000, 20000, 1000, 'Dyson V12 Detect Slim Absolute สภาพ 90% ใช้ 6 เดือน แบตยังดี สาย+หัวครบ', 'dyson+vacuum', 'กรุงเทพมหานคร'],

            // ===== DEFAULT (10 items — เวลาเหลือเยอะ ยังไม่ urgent) =====
            ['Electronics', 'Audio & Headphones', 'Sony WH-1000XM5 หูฟัง', 'default', 3000, 9000, 500, 'หูฟังตัดเสียงอันดับ 1 สภาพ 95% มีกล่อง อุปกรณ์ครบ', 'headphones', 'กรุงเทพมหานคร'],
            ['Fashion', 'Watches & Jewelry', 'นาฬิกา Casio G-Shock GA-2100', 'default', 1500, 5000, 200, 'G-Shock CasiOak GA-2100-1A1 สีดำ ของแท้ ประกัน CMG', 'wristwatch', 'ปทุมธานี'],
            ['Collectibles', 'Trading Cards', 'Pokemon Card Charizard Holo', 'default', 5000, 15000, 1000, 'การ์ดโปเกมอน Charizard Holo 1st Edition สภาพ PSA 8 หายากมาก', 'trading+cards', 'กรุงเทพมหานคร'],
            ['Collectibles', 'Coins & Stamps', 'เหรียญ ร.5 หายาก ปี 2400', 'default', 20000, 50000, 2000, 'เหรียญหนึ่งบาทรัชกาลที่ 5 ปี พ.ศ. 2400 สภาพสวย ผ่านการรับรอง', 'antique+coin', 'กรุงเทพมหานคร'],
            ['Home', 'Home Decor', 'โคมไฟ Nordic สไตล์มินิมอล', 'default', 800, 3000, 100, 'โคมไฟตั้งพื้น สไตล์ Nordic ขาไม้ โป๊ะผ้า สูง 150 cm ของใหม่', 'modern+lamp', 'เชียงใหม่'],
            ['Vehicles', 'Motorcycles', 'Ducati Monster 821 ปี 2020', 'default', 250000, 400000, 10000, 'Ducati Monster 821 ปี 2020 สีแดง ไมล์ 8,000 km ศูนย์ดูแล', 'ducati+motorcycle', 'กรุงเทพมหานคร'],
            ['Others', 'Musical Instruments', 'กีตาร์ Yamaha F310', 'default', 2500, 5500, 200, 'กีตาร์โปร่ง Yamaha F310 สภาพ 90% เสียงดี แถมกระเป๋า คาโป้', 'acoustic+guitar', 'เชียงใหม่'],
            ['Fashion', 'Watches & Jewelry', 'นาฬิกา Rolex Submariner Date', 'default', 150000, 280000, 5000, 'Rolex Submariner Date 41mm สภาพ 95% มีกล่องและใบ cert ครบ', 'rolex+watch', 'กรุงเทพมหานคร'],
            ['Electronics', 'Smartphones & Tablets', 'iPad Air M2 256GB WiFi สีม่วง', 'default', 18000, 27000, 1000, 'iPad Air M2 chip 256GB WiFi สีม่วง สภาพใหม่มาก ใช้ 2 เดือน มีกล่องครบ', 'ipad+tablet', 'กรุงเทพมหานคร'],
            ['Fashion', 'Bags & Accessories', 'กระเป๋า Louis Vuitton Neverfull MM', 'default', 25000, 42000, 2000, 'LV Neverfull MM Monogram Canvas สภาพ 90% ของแท้ มีใบเสร็จและ dust bag', 'louis+vuitton', 'ปทุมธานี'],

            // ===== ENDED (4 items — หมดเวลาแล้ว มีบาง bid) =====
            ['Home', 'Kitchen & Dining', 'ชุดจานชามเซรามิค 24 ชิ้น', 'ended', 1200, 4000, 200, 'ชุดจานชามเซรามิคญี่ปุ่น 24 ชิ้น ลายดอกไม้ ใหม่ยังไม่แกะ', 'ceramic+plates', 'นนทบุรี'],
            ['Vehicles', 'Parts & Accessories', 'ล้อแม็ก TE37 18 นิ้ว ของแท้', 'ended', 25000, 55000, 2000, 'ล้อแม็ก Volk Racing TE37 ขอบ 18 นิ้ว 5 รู PCD 114.3 ของแท้ Made in Japan', 'car+wheel+rim', 'กรุงเทพมหานคร'],
            ['Others', 'Sports & Fitness', 'ลู่วิ่งไฟฟ้า Xiaomi WalkingPad', 'ended', 5000, 12000, 500, 'ลู่วิ่งไฟฟ้า Xiaomi WalkingPad R1 Pro พับเก็บได้ ใช้งาน 6 เดือน', 'treadmill', 'กรุงเทพมหานคร'],
            ['Fashion', 'Shoes & Footwear', 'Nike Dunk Low Panda ของแท้', 'ended', 3500, 6000, 300, 'Nike Dunk Low Retro White/Black (Panda) Size 42 EU ของแท้ 100%', 'nike+dunk', 'เชียงใหม่'],

            // ===== INCOMING (2 items — ยังไม่ถึงเวลาเริ่มประมูล) =====
            ['Electronics', 'Audio & Headphones', 'หูฟัง Marshall Major IV Bluetooth', 'incoming', 2000, 4500, 300, 'Marshall Major IV หูฟังไร้สาย Bluetooth แบตอึด 80+ ชม. สภาพ 99%', 'marshall+headphones', 'นนทบุรี'],
            ['Electronics', 'Computers & Laptops', 'กล้อง Canon EOS R6 Mark II Body', 'incoming', 55000, 75000, 3000, 'Canon EOS R6 Mark II Body สภาพดีมาก ชัตเตอร์ 8,000 ครั้ง มีกล่องครบ', 'canon+camera', 'กรุงเทพมหานคร'],
        ];

        // จำนวน bids ตาม tag
        $bidCountsMap = [
            'hot' => [12, 12, 11, 12, 10, 13, 15, 10],
            'ending' => [0, 0, 0],
            'default' => [3, 2, 0, 2, 0, 0, 0, 0, 0, 0],
            'ended' => [1, 3, 2, 0],
            'incoming' => [0, 0],
        ];

        $tagCounters = ['hot' => 0, 'ending' => 0, 'default' => 0, 'ended' => 0, 'incoming' => 0];

        foreach ($products as $data) {
            [$categoryName, $subcategoryName, $name, $tag, $startingPrice, $buyoutPrice, $bidIncrement, $description, $imageKeyword, $location] = $data;

            // ดึง category + subcategory จาก DB
            $category = Category::where('name', $categoryName)->first();
            $subcategory = Subcategory::where('name', $subcategoryName)
                ->where('category_id', $category->id)
                ->first();

            // กำหนดเจ้าของสินค้า (สลับ sellers)
            $seller = $sellers[$tagCounters[$tag] % count($sellers)];

            // กำหนดเวลาตาม tag (ตาม logic ใหม่)
            switch ($tag) {
                case 'hot':
                    // active มาหลายวัน มีคน bid เยอะ
                    $auctionStart = now()->subDays(3);
                    $auctionEnd = now()->addDays(4);
                    break;
                case 'ending':
                    // กำลังจะจบ (เหลือ 1-5 ชม. — ภายใน 6 ชม.)
                    $auctionStart = now()->subDays(2);
                    $auctionEnd = now()->addHours(rand(1, 5));
                    break;
                case 'default':
                    // ยังมีเวลาเหลือเยอะ (3-7 วัน)
                    $auctionStart = now()->subDays(1);
                    $auctionEnd = now()->addDays(rand(3, 7));
                    break;
                case 'ended':
                    // หมดเวลาแล้ว
                    $auctionStart = now()->subDays(7);
                    $auctionEnd = now()->subHours(rand(1, 48));
                    break;
                case 'incoming':
                    // ยังไม่ถึงเวลาเริ่ม (auction_start_time ในอนาคต)
                    $auctionStart = now()->addDays(rand(1, 3));
                    $auctionEnd = now()->addDays(rand(5, 10));
                    break;
            }

            $numBids = $bidCountsMap[$tag][$tagCounters[$tag]] ?? 0;
            $tagCounters[$tag]++;

            // สร้าง Product (bid_increment กำหนดเองโดย seller)
            $product = Product::create([
                'user_id' => $seller->id,
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'name' => $name,
                'description' => $description,
                'starting_price' => $startingPrice,
                'current_price' => $startingPrice,
                'bid_increment' => $bidIncrement,
                'buyout_price' => $buyoutPrice,
                'auction_start_time' => $auctionStart,
                'auction_end_time' => $auctionEnd,
                'location' => $location,
                'status' => 'active',
            ]);

            // อัปเดต created_at
            $product->update(['created_at' => $auctionStart]);

            // ดาวน์โหลดรูป
            $this->downloadImage($product, $name, $imageKeyword);

            // สร้าง Bids
            if ($numBids > 0) {
                $this->createBids($product, $bidders, $seller, $numBids, $startingPrice, $bidIncrement);
            }

            echo "  ✓ [{$tag}] {$name} (bids: {$numBids})" . PHP_EOL;
        }

        echo PHP_EOL . "Total: " . count($products) . " products seeded!" . PHP_EOL;
    }

    private function downloadImage(Product $product, string $name, string $keyword): void
    {
        try {
            $filename = 'seed_' . Str::slug($name) . '.jpg';
            $filepath = "products/{$filename}";

            // ถ้ารูปมีอยู่แล้ว ไม่ต้องดาวน์โหลดซ้ำ
            if (Storage::disk('public')->exists($filepath)) {
                $product->update(['picture' => $filepath]);
                return;
            }

            // ดาวน์โหลดจาก picsum.photos
            $imageUrl = "https://picsum.photos/seed/{$keyword}/800/600";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'follow_location' => true,
                ],
            ]);
            $imageContent = @file_get_contents($imageUrl, false, $context);

            if ($imageContent && strlen($imageContent) > 1000) {
                Storage::disk('public')->put($filepath, $imageContent);
                $product->update(['picture' => $filepath]);
            }
        }
        catch (\Exception $e) {
            echo "  Skip (error): {$name}" . PHP_EOL;
        }
    }

    private function createBids(Product $product, array $bidders, User $seller, int $numBids, float $startingPrice, float $bidIncrement): void
    {
        $currentBidPrice = $startingPrice;

        for ($i = 0; $i < $numBids; $i++) {
            $currentBidPrice += $bidIncrement;

            // สลับ bidder (ไม่ใช่ seller)
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