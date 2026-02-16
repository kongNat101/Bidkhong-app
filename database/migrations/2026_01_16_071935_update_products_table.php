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
        Schema::table('products', function (Blueprint $table){
           
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->after('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subcategory_id')->after('category_id')->nullable()->constrained()->onDelete('set null');

            $table->decimal('min_price', 10, 2)->after('starting_price');
            $table->decimal('buyout_price', 10, 2)->nullable()->after('min_price');
            $table->string('location')->nullable()->after('description');
            $table->string('picture')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function(Blueprint $table){
            $table->dropForeign(['user_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['subcategory_id']);

            $table->dropColumn(['user_id', 'category_id', 
            'subcategory_id', 'min_price', 'buyout_price', 'location', 'picture']);
        });
    }
};
