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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['topup', 'withdraw', 'bid_placed', 'bid_refund', 'auction_won', 'auction_sold']);
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->string('reference_type')->nullable(); // product, bid, order
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('balance_after', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
