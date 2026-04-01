<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bids MODIFY COLUMN status ENUM('active','outbid','won','lost','completed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE bids MODIFY COLUMN status ENUM('active','outbid','won','lost') NOT NULL DEFAULT 'active'");
    }
};
