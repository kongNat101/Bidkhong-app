<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ผู้รับการแจ้งเตือน
            $table->string('type'); // ประเภท: 'outbid', 'won', 'lost', 'new_bid'
            $table->string('title'); // หัวข้อ
            $table->text('message'); // ข้อความ
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade'); // สินค้าที่เกี่ยวข้อง
            $table->boolean('is_read')->default(false); // อ่านแล้วหรือยัง
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};