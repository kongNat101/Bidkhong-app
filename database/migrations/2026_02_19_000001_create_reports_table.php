<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reported_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reported_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->enum('type', ['scam', 'fake_product', 'harassment', 'inappropriate_content', 'other']);
            $table->text('description');
            $table->json('evidence_images')->nullable();
            $table->enum('status', ['pending', 'reviewing', 'resolved', 'dismissed'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
