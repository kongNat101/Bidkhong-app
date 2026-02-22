<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Timeline timestamps
            $table->timestamp('reviewing_at')->nullable()->after('resolved_at');

            // Admin reply
            $table->text('admin_reply')->nullable()->after('admin_note');
            $table->timestamp('admin_reply_at')->nullable()->after('admin_reply');
            $table->foreignId('admin_reply_by')->nullable()->after('admin_reply_at')
                ->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['admin_reply_by']);
            $table->dropColumn(['reviewing_at', 'admin_reply', 'admin_reply_at', 'admin_reply_by']);
        });
    }
};
