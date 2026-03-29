<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Product;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ProductWatch;
use Illuminate\Database\Seeder;

class NotificationTestSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n=== สร้างข้อมูลทดสอบ Notification ===\n\n";

        // === 1. สร้าง Admin (ถ้ายังไม่มี) ===
        $admin = User::firstOrCreate(
            ['email' => 'admin@bidkhong.com'],
            [
                'name' => 'BidKhong Admin',
                'password' => 'admin123',
                'phone_number' => '0900000000',
                'role' => 'admin',
            ]
        );
        if (!$admin->wallet) {
            $admin->wallet()->create(['balance_available' => 0, 'balance_total' => 0, 'balance_pending' => 0, 'withdraw' => 0, 'deposit' => 0]);
        }
        echo "✓ Admin: admin@bidkhong.com / admin123\n";

        // === 2. สร้าง Seller ===
        $seller = User::firstOrCreate(
            ['email' => 'seller@noti.test'],
            [
                'name' => 'Noti Seller',
                'password' => 'password123',
                'phone_number' => '0891000001',
            ]
        );
        if (!$seller->wallet) {
            $seller->wallet()->create(['balance_available' => 100000, 'balance_total' => 100000, 'balance_pending' => 0, 'withdraw' => 0, 'deposit' => 100000]);
        } else {
            $seller->wallet->update(['balance_available' => 100000, 'balance_total' => 100000, 'deposit' => 100000]);
        }
        echo "✓ Seller: seller@noti.test / password123 (balance: 100,000)\n";

        // === 3. สร้าง Buyer A ===
        $buyerA = User::firstOrCreate(
            ['email' => 'buyerA@noti.test'],
            [
                'name' => 'Buyer A',
                'password' => 'password123',
                'phone_number' => '0891000002',
            ]
        );
        if (!$buyerA->wallet) {
            $buyerA->wallet()->create(['balance_available' => 500000, 'balance_total' => 500000, 'balance_pending' => 0, 'withdraw' => 0, 'deposit' => 500000]);
        } else {
            $buyerA->wallet->update(['balance_available' => 500000, 'balance_total' => 500000, 'deposit' => 500000]);
        }
        echo "✓ Buyer A: buyerA@noti.test / password123 (balance: 500,000)\n";

        // === 4. สร้าง Buyer B ===
        $buyerB = User::firstOrCreate(
            ['email' => 'buyerB@noti.test'],
            [
                'name' => 'Buyer B',
                'password' => 'password123',
                'phone_number' => '0891000003',
            ]
        );
        if (!$buyerB->wallet) {
            $buyerB->wallet()->create(['balance_available' => 500000, 'balance_total' => 500000, 'balance_pending' => 0, 'withdraw' => 0, 'deposit' => 500000]);
        } else {
            $buyerB->wallet->update(['balance_available' => 500000, 'balance_total' => 500000, 'deposit' => 500000]);
        }
        echo "✓ Buyer B: buyerB@noti.test / password123 (balance: 500,000)\n";

        // === 5. หา category ===
        $category = Category::first();
        $subcategory = Subcategory::where('category_id', $category->id)->first();

        if (!$category || !$subcategory) {
            echo "❌ ไม่มี category ในระบบ ให้รัน CategorySeeder ก่อน\n";
            return;
        }

        // === 6. สร้างสินค้า: รอ Admin approve (ทดสอบ approve/reject noti) ===
        $productPending1 = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'name' => '[NOTI TEST] สินค้ารอ Approve',
            'description' => 'สินค้าสำหรับทดสอบ noti ระบบ approve',
            'starting_price' => 1000,
            'current_price' => 1000,
            'bid_increment' => 100,
            'buyout_price' => 5000,
            'auction_start_time' => now(),
            'auction_end_time' => now()->addHours(3),
            'status' => 'pending',
        ]);
        echo "✓ สินค้ารอ approve: ID {$productPending1->id}\n";

        $productPending2 = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'name' => '[NOTI TEST] สินค้ารอ Reject',
            'description' => 'สินค้าสำหรับทดสอบ noti ระบบ reject',
            'starting_price' => 2000,
            'current_price' => 2000,
            'bid_increment' => 200,
            'auction_start_time' => now(),
            'auction_end_time' => now()->addHours(3),
            'status' => 'pending',
        ]);
        echo "✓ สินค้ารอ reject: ID {$productPending2->id}\n";

        // === 7. สร้างสินค้า: active + มี bid (ทดสอบ outbid / won / lost / sold) ===
        $productBid = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'name' => '[NOTI TEST] สินค้าประมูล - ทดสอบ Outbid',
            'description' => 'ให้ Buyer A bid ก่อน แล้ว Buyer B bid แซง',
            'starting_price' => 1000,
            'current_price' => 1000,
            'bid_increment' => 100,
            'buyout_price' => 10000,
            'auction_start_time' => now()->subMinutes(10),
            'auction_end_time' => now()->addHours(1),
            'status' => 'active',
        ]);
        echo "✓ สินค้าทดสอบ bid: ID {$productBid->id}\n";

        // === 8. สร้างสินค้า: active + ใกล้หมดเวลา 50 นาที (ทดสอบ noti 1 ชม.) ===
        $productEnding1h = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'name' => '[NOTI TEST] สินค้าใกล้หมด 1 ชม.',
            'description' => 'สินค้าจะหมดเวลาใน 50 นาที — ทดสอบ noti auction_ending_1h',
            'starting_price' => 3000,
            'current_price' => 3500,
            'bid_increment' => 500,
            'auction_start_time' => now()->subHours(2),
            'auction_end_time' => now()->addMinutes(50),
            'status' => 'active',
        ]);
        // สร้าง bid ให้ Buyer A เพื่อให้ได้ noti
        Bid::create([
            'user_id' => $buyerA->id,
            'product_id' => $productEnding1h->id,
            'price' => 3500,
            'time' => now()->subHours(1),
            'status' => 'active',
        ]);
        echo "✓ สินค้าใกล้หมด 1 ชม.: ID {$productEnding1h->id} (Buyer A bid อยู่)\n";

        // === 9. สร้างสินค้า: active + ใกล้หมดเวลา 10 นาที (ทดสอบ noti 15 นาที) ===
        $productEnding15m = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'name' => '[NOTI TEST] สินค้าใกล้หมด 15 นาที',
            'description' => 'สินค้าจะหมดเวลาใน 10 นาที — ทดสอบ noti auction_ending_15m',
            'starting_price' => 2000,
            'current_price' => 2500,
            'bid_increment' => 500,
            'auction_start_time' => now()->subHours(3),
            'auction_end_time' => now()->addMinutes(10),
            'status' => 'active',
        ]);
        Bid::create([
            'user_id' => $buyerB->id,
            'product_id' => $productEnding15m->id,
            'price' => 2500,
            'time' => now()->subHours(1),
            'status' => 'active',
        ]);
        echo "✓ สินค้าใกล้หมด 15 นาที: ID {$productEnding15m->id} (Buyer B bid อยู่)\n";

        // === 10. สร้างสินค้า: หมดเวลาแล้ว ไม่มี bid (ทดสอบ no-bids noti) ===
        $productNoBids = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'name' => '[NOTI TEST] สินค้าหมดเวลา ไม่มี bid',
            'description' => 'ทดสอบ noti auction_ended_no_bids',
            'starting_price' => 5000,
            'current_price' => 5000,
            'bid_increment' => 500,
            'auction_start_time' => now()->subDays(1),
            'auction_end_time' => now()->subMinutes(5),
            'status' => 'active',
        ]);
        echo "✓ สินค้าหมดเวลา (ไม่มี bid): ID {$productNoBids->id}\n";

        // === 11. สร้างสินค้า: หมดเวลาแล้ว มี bid (ทดสอบ won/lost/sold noti) ===
        $productExpiredWithBids = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'name' => '[NOTI TEST] สินค้าหมดเวลา มี bid',
            'description' => 'ทดสอบ noti won/lost/sold เมื่อประมูลจบ',
            'starting_price' => 1000,
            'current_price' => 3000,
            'bid_increment' => 500,
            'auction_start_time' => now()->subDays(1),
            'auction_end_time' => now()->subMinutes(3),
            'status' => 'active',
        ]);
        Bid::create([
            'user_id' => $buyerA->id,
            'product_id' => $productExpiredWithBids->id,
            'price' => 2000,
            'time' => now()->subHours(5),
            'status' => 'outbid',
        ]);
        Bid::create([
            'user_id' => $buyerB->id,
            'product_id' => $productExpiredWithBids->id,
            'price' => 3000,
            'time' => now()->subHours(3),
            'status' => 'active',
        ]);
        echo "✓ สินค้าหมดเวลา (มี bid): ID {$productExpiredWithBids->id} (A bid 2000, B bid 3000)\n";

        // === 12. สร้างสินค้า: สำหรับ watch (ทดสอบ watcher noti) ===
        $productWatch = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'name' => '[NOTI TEST] สินค้ารอเปิดประมูล (Watch)',
            'description' => 'Buyer A watch สินค้านี้ → เมื่อ admin approve จะได้ noti',
            'starting_price' => 5000,
            'current_price' => 5000,
            'bid_increment' => 500,
            'auction_start_time' => now()->subMinutes(5),
            'auction_end_time' => now()->addHours(6),
            'status' => 'pending',
        ]);
        ProductWatch::firstOrCreate([
            'user_id' => $buyerA->id,
            'product_id' => $productWatch->id,
        ]);
        echo "✓ สินค้า Watch (pending): ID {$productWatch->id} (Buyer A กด watch แล้ว)\n";

        // === สรุป ===
        echo "\n=== สรุปข้อมูลทดสอบ ===\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "👤 Accounts:\n";
        echo "   Admin:    admin@bidkhong.com / admin123\n";
        echo "   Seller:   seller@noti.test / password123\n";
        echo "   Buyer A:  buyerA@noti.test / password123\n";
        echo "   Buyer B:  buyerB@noti.test / password123\n";
        echo "\n";
        echo "📦 Products:\n";
        echo "   ID {$productPending1->id}: รอ Approve → ทดสอบ noti approve\n";
        echo "   ID {$productPending2->id}: รอ Reject → ทดสอบ noti reject\n";
        echo "   ID {$productBid->id}: Active → ทดสอบ outbid (Buyer A bid → Buyer B bid แซง)\n";
        echo "   ID {$productEnding1h->id}: หมดใน 50 นาที → ทดสอบ noti 1 ชม. (Buyer A bid อยู่)\n";
        echo "   ID {$productEnding15m->id}: หมดใน 10 นาที → ทดสอบ noti 15 นาที (Buyer B bid อยู่)\n";
        echo "   ID {$productNoBids->id}: หมดเวลา ไม่มี bid → ทดสอบ noti no-bids\n";
        echo "   ID {$productExpiredWithBids->id}: หมดเวลา มี bid → ทดสอบ won/lost/sold\n";
        echo "   ID {$productWatch->id}: Pending + Buyer A watch → ทดสอบ watcher noti\n";
        echo "\n";
        echo "🧪 ขั้นตอนทดสอบ:\n";
        echo "   1. Admin approve ID {$productPending1->id} → Seller ได้ noti 'สินค้าอนุมัติ'\n";
        echo "   2. Admin reject ID {$productPending2->id} → Seller ได้ noti 'สินค้าปฏิเสธ'\n";
        echo "   3. Admin approve ID {$productWatch->id} → Buyer A ได้ noti 'สินค้าที่ติดตามเปิดประมูล'\n";
        echo "   4. Buyer A bid ID {$productBid->id} → แล้ว Buyer B bid แซง → Buyer A ได้ noti 'outbid'\n";
        echo "   5. รัน: php artisan auctions:notify-ending-soon\n";
        echo "      → Buyer A ได้ noti 'ใกล้หมด 1 ชม.' (ID {$productEnding1h->id})\n";
        echo "      → Buyer B ได้ noti 'ใกล้หมด 15 นาที' (ID {$productEnding15m->id})\n";
        echo "   6. รัน: php artisan auctions:close-expired\n";
        echo "      → Seller ได้ noti 'ไม่มี bid' (ID {$productNoBids->id})\n";
        echo "      → Buyer B ได้ noti 'won', Buyer A ได้ noti 'lost', Seller ได้ noti 'sold' (ID {$productExpiredWithBids->id})\n";
        echo "   7. Post-Auction: confirm → ship → receive (ดูคู่มือทดสอบ)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
