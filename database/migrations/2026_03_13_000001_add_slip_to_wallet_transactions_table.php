<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('slip_image')->nullable()->after('balance_after');
            $table->enum('slip_status', ['pending', 'verified', 'rejected'])->nullable()->after('slip_image');
            $table->json('slip_data')->nullable()->after('slip_status');
            $table->timestamp('verified_at')->nullable()->after('slip_data');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn(['slip_image', 'slip_status', 'slip_data', 'verified_at']);
        });
    }
};
