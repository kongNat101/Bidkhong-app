<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // เพิ่ม slip_ref สำหรับป้องกันสลิปซ้ำ
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('slip_ref')->nullable()->unique()->after('slip_data');
        });

        // แก้ wallets decimal ให้ตรงกับ wallet_transactions (12,2)
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance_available', 12, 2)->default(0)->change();
            $table->decimal('balance_total', 12, 2)->default(0)->change();
            $table->decimal('balance_pending', 12, 2)->default(0)->change();
            $table->decimal('withdraw', 12, 2)->default(0)->change();
            $table->decimal('deposit', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique(['slip_ref']);
            $table->dropColumn('slip_ref');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance_available', 10, 2)->default(0)->change();
            $table->decimal('balance_total', 10, 2)->default(0)->change();
            $table->decimal('balance_pending', 10, 2)->default(0)->change();
            $table->decimal('withdraw', 10, 2)->default(0)->change();
            $table->decimal('deposit', 10, 2)->default(0)->change();
        });
    }
};
