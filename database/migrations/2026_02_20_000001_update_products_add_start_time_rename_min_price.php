<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // เพิ่ม auction_start_time
            $table->timestamp('auction_start_time')->nullable()->after('auction_end_time');

            // เปลี่ยน min_price → bid_increment (จำนวนบิดขั้นต่ำที่ seller กำหนดเอง)
            $table->renameColumn('min_price', 'bid_increment');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('auction_start_time');
            $table->renameColumn('bid_increment', 'min_price');
        });
    }
};
