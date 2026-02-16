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
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); //คนที่ปรมูล
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); //สินค้าที่ถูกประมูล
            $table->decimal('price', 10, 2); //ราคาที่ประมูล
            $table->timestamp('time')->useCurrent(); // เวลาที่ประมูล
            $table->enum('status', ['active', 'outbid', 'won', 'lost'])->default('active'); //สถานะ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
