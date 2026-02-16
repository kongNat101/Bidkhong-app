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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance_available', 10, 2)->default(0);
            $table->decimal('balance_total', 10, 2)->default(0);
            $table->decimal('balance_pending', 10, 2)->default(0);
            $table->decimal('withdraw', 10, 2)->default(0);
            $table->decimal('deposit', 10, 2)->default(0);
            $table->timestamp('w_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
