<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('withdraw_status')->nullable()->after('balance_after');
            $table->unsignedBigInteger('confirmed_by')->nullable()->after('withdraw_status');
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');

            $table->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['withdraw_status', 'confirmed_by', 'confirmed_at']);
        });
    }
};
