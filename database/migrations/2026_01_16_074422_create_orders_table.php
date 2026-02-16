<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); //ผู้ชนะการประมูล
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // สินค้าที่ชนะการประมูล
            $table->decimal('final_price', 10, 2); // ราคาที่ชนะการประมูล
            $table->boolean('o_verified')->default(false); // ยืนยันคำสั่งซื้อยัง
            $table->timestamp('order_date')->useCurrent(); //วันที่สร้างคำสั่งซื้อ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
