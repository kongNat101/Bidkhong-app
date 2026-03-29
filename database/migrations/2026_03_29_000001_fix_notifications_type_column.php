<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // เปลี่ยน type จาก ENUM เป็น VARCHAR เพื่อรองรับ type ใหม่ทั้งหมด
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type VARCHAR(255) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('outbid','won','lost','sold','new_bid','order','system') NOT NULL");
    }
};
