<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // แก้ enum status เพิ่ม pending, rejected
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled', 'rejected') DEFAULT 'pending'");

        Schema::table('products', function (Blueprint $table) {
            $table->text('admin_note')->nullable()->after('status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('admin_note');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['admin_note', 'approved_by', 'approved_at']);
        });

        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
    }
};
