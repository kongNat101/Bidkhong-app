<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('status', [
                'pending_buyer_confirm',
                'confirmed',
                'shipped',
                'completed',
                'disputed',
                'cancelled'
            ])->default('pending_buyer_confirm');
            $table->timestamp('buyer_confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('confirm_deadline')->nullable();
            $table->timestamp('ship_deadline')->nullable();
            $table->timestamp('receive_deadline')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropColumn([
                'seller_id', 'status',
                'buyer_confirmed_at',
                'shipped_at', 'received_at',
                'confirm_deadline', 'ship_deadline', 'receive_deadline',
            ]);
        });
    }
};