<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ลบ comment จาก reviews (เก็บแค่คะแนนดาว)
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('comment');
        });

        // เพิ่ม rating + total_reviews ใน users table
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(0)->after('role');
            $table->unsignedInteger('total_reviews')->default(0)->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('rating');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rating', 'total_reviews']);
        });
    }
};
