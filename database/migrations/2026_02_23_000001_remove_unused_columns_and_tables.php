<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ลบคอลัมน์ที่ซ้ำซ้อนกับ created_at และตาราง sessions ที่ไม่ได้ใช้
     *
     * คอลัมน์ที่ลบ:
     * - users.join_date (ซ้ำกับ created_at)
     * - orders.order_date (ซ้ำกับ created_at)
     * - wallets.w_time (ซ้ำกับ created_at)
     *
     * ตารางที่ลบ:
     * - sessions (API ใช้ Sanctum token ไม่ต้องการ session)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('join_date');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_date');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('w_time');
        });

        Schema::dropIfExists('sessions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('join_date')->nullable()->after('phone_number');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('order_date')->nullable()->after('receive_deadline');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->timestamp('w_time')->nullable()->after('deposit');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
};
