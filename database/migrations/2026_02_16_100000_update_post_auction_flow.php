<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // อัปเดท status enum: เพิ่ม pending_buyer_confirm
        // สำหรับ MySQL ต้อง alter column
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending_confirm',
            'pending_buyer_confirm',
            'confirmed',
            'shipped',
            'completed',
            'disputed',
            'cancelled'
        ) DEFAULT 'pending_buyer_confirm'");

        // เปลี่ยน orders ที่เป็น pending_confirm → pending_buyer_confirm
        DB::table('orders')
            ->where('status', 'pending_confirm')
            ->update(['status' => 'pending_buyer_confirm']);

        // ลบ column seller_confirmed_at (ไม่ใช้แล้ว)
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('seller_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('seller_confirmed_at')->nullable();
        });

        // เปลี่ยน pending_buyer_confirm กลับเป็น pending_confirm
        DB::table('orders')
            ->where('status', 'pending_buyer_confirm')
            ->update(['status' => 'pending_confirm']);

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
            'pending_confirm',
            'confirmed',
            'shipped',
            'completed',
            'disputed',
            'cancelled'
        ) DEFAULT 'pending_confirm'");
    }
};
