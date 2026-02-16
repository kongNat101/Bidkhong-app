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
        Schema::table('users', function (Blueprint $table){
            $table->string('phone_number')->nullable()->after('email');
            $table->timestamp('join_date')->useCurrent()->after('password');
            $table->enum('role', ['user', 'admin'])->default('user')->after('join_date');

            $table->dropColumn('balance');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users',function (Blueprint $table){
            $table->dropColumn(['phone_number', 'join_date', 'role']);

            $table->decimal('balance', 10, 2)->default(0);
        });
    }
};
