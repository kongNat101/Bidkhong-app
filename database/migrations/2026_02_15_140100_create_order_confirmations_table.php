<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['buyer', 'seller']);
            $table->string('phone', 20);
            $table->string('line_id', 100)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            // คน 1 คน confirm ได้แค่ 1 ครั้งต่อ order
            $table->unique(['order_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_confirmations');
    }
};