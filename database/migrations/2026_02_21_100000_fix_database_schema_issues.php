<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // === Fix 1: users.phone_number → NOT NULL + UNIQUE ===
        // แก้ users ที่ phone_number เป็น NULL หรือซ้ำกัน → ใส่เบอร์ไม่ซ้ำ
        $usersToFix = DB::table('users')
            ->where(function ($q) {
                $q->whereNull('phone_number')
                  ->orWhere('phone_number', '')
                  ->orWhere('phone_number', '0000000000');
            })
            ->pluck('id');
        foreach ($usersToFix as $userId) {
            DB::table('users')->where('id', $userId)->update([
                'phone_number' => '0000' . str_pad($userId, 6, '0', STR_PAD_LEFT),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable(false)->unique()->change();
        });

        // === Fix 2: wallet_transactions.type ENUM → เพิ่ม values ที่ขาด ===
        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM(
            'topup',
            'withdraw',
            'bid_placed',
            'bid_refund',
            'auction_won',
            'auction_sold',
            'escrow_hold',
            'escrow_release',
            'escrow_refund'
        ) NOT NULL");

        // === Fix 3: products.image_url → DROP COLUMN (legacy ไม่ใช้แล้ว) ===
        if (Schema::hasColumn('products', 'image_url')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('image_url');
            });
        }

        // === Fix 4: notifications.type → เปลี่ยนจาก VARCHAR เป็น ENUM ===
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'outbid',
            'won',
            'lost',
            'sold',
            'new_bid',
            'order',
            'system'
        ) NOT NULL");

        // === Fix 5: orders.o_verified → DROP COLUMN (ซ้ำซ้อนกับ status) ===
        if (Schema::hasColumn('orders', 'o_verified')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('o_verified');
            });
        }
    }

    public function down(): void
    {
        // Revert users.phone_number
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone_number']);
            $table->string('phone_number')->nullable()->change();
        });

        // Revert wallet_transactions.type
        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM(
            'topup', 'withdraw', 'bid_placed', 'bid_refund', 'auction_won', 'auction_sold'
        ) NOT NULL");

        // Revert products.image_url
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_url')->nullable();
        });

        // Revert notifications.type
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->change();
        });

        // Revert orders.o_verified
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('o_verified')->default(false);
        });
    }
};