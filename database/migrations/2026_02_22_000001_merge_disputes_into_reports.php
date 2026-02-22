<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. เพิ่ม columns ใหม่ใน reports
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('reported_product_id')
                ->constrained('orders')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable()->after('admin_note');
        });

        // 2. ขยาย type ENUM เพิ่ม 'dispute'
        DB::statement("ALTER TABLE reports MODIFY COLUMN `type` ENUM('scam','fake_product','harassment','inappropriate_content','other','dispute') NOT NULL");

        // 3. ขยาย status ENUM เพิ่ม dispute statuses
        DB::statement("ALTER TABLE reports MODIFY COLUMN `status` ENUM('pending','reviewing','resolved','dismissed','open','resolved_buyer','resolved_seller') NOT NULL DEFAULT 'pending'");

        // 4. ทำให้ reported_user_id nullable (disputes ใช้ seller จาก order)
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['reported_user_id']);
        });
        DB::statement('ALTER TABLE reports MODIFY COLUMN reported_user_id BIGINT UNSIGNED NULL');
        Schema::table('reports', function (Blueprint $table) {
            $table->foreign('reported_user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 5. ทำให้ description nullable (disputes map reason → description)
        DB::statement('ALTER TABLE reports MODIFY COLUMN description TEXT NULL');

        // 6. ย้ายข้อมูลจาก disputes → reports
        if (Schema::hasTable('disputes')) {
            $disputes = DB::table('disputes')
                ->join('orders', 'disputes.order_id', '=', 'orders.id')
                ->select('disputes.*', 'orders.seller_id')
                ->get();

            foreach ($disputes as $dispute) {
                DB::table('reports')->insert([
                    'reporter_id' => $dispute->reporter_id,
                    'reported_user_id' => $dispute->seller_id,
                    'reported_product_id' => null,
                    'order_id' => $dispute->order_id,
                    'type' => 'dispute',
                    'description' => $dispute->reason,
                    'evidence_images' => $dispute->evidence_images,
                    'status' => $dispute->status,
                    'admin_note' => $dispute->admin_note,
                    'resolved_at' => $dispute->resolved_at,
                    'created_at' => $dispute->created_at,
                    'updated_at' => $dispute->updated_at,
                ]);
            }

            // 7. Drop disputes table
            Schema::dropIfExists('disputes');
        }
    }

    public function down(): void
    {
        // สร้าง disputes table กลับมา
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->text('reason');
            $table->json('evidence_images')->nullable();
            $table->enum('status', ['open', 'resolved_buyer', 'resolved_seller'])->default('open');
            $table->text('admin_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // ย้ายข้อมูล dispute กลับ
        $disputeReports = DB::table('reports')->where('type', 'dispute')->get();
        foreach ($disputeReports as $report) {
            DB::table('disputes')->insert([
                'order_id' => $report->order_id,
                'reporter_id' => $report->reporter_id,
                'reason' => $report->description,
                'evidence_images' => $report->evidence_images,
                'status' => $report->status,
                'admin_note' => $report->admin_note,
                'resolved_at' => $report->resolved_at,
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
            ]);
        }

        // ลบ dispute-type reports
        DB::table('reports')->where('type', 'dispute')->delete();

        // คืน ENUMs
        DB::statement("ALTER TABLE reports MODIFY COLUMN `type` ENUM('scam','fake_product','harassment','inappropriate_content','other') NOT NULL");
        DB::statement("ALTER TABLE reports MODIFY COLUMN `status` ENUM('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending'");

        // คืน reported_user_id เป็น NOT NULL
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['reported_user_id']);
        });
        DB::statement('ALTER TABLE reports MODIFY COLUMN reported_user_id BIGINT UNSIGNED NOT NULL');
        Schema::table('reports', function (Blueprint $table) {
            $table->foreign('reported_user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // คืน description เป็น NOT NULL
        DB::statement('ALTER TABLE reports MODIFY COLUMN description TEXT NOT NULL');

        // ลบ columns ที่เพิ่ม
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id', 'resolved_at']);
        });
    }
};
