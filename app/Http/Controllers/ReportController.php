<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    // POST /api/reports - สร้าง report
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reported_user_id' => ['required', 'integer', 'exists:users,id'],
            'reported_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'type' => ['required', 'string', 'in:scam,fake_product,harassment,inappropriate_content,other'],
            'description' => ['required', 'string', 'max:1000'],
            'evidence_images' => ['nullable', 'array', 'max:5'],
            'evidence_images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $userId = $request->user()->id;

        // ห้าม report ตัวเอง
        if ($userId == $validated['reported_user_id']) {
            return response()->json([
                'message' => 'You cannot report yourself.',
            ], 422);
        }

        // อัปโหลดรูปหลักฐาน
        $imagePaths = [];
        if ($request->hasFile('evidence_images')) {
            foreach ($request->file('evidence_images') as $image) {
                $path = $image->store('reports', 'public');
                $imagePaths[] = $path;
            }
        }

        $report = Report::create([
            'reporter_id' => $userId,
            'reported_user_id' => $validated['reported_user_id'],
            'reported_product_id' => $validated['reported_product_id'] ?? null,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'evidence_images' => $imagePaths ?: null,
            'status' => 'pending',
        ]);

        $report->load(['reportedUser:id,name', 'reportedProduct:id,name']);

        return response()->json([
            'message' => 'Report submitted successfully. An admin will review your report.',
            'report' => $report,
        ], 201);
    }

    // GET /api/reports - ดู report ที่ตัวเองสร้าง
    public function index(Request $request)
    {
        $reports = Report::where('reporter_id', $request->user()->id)
            ->with(['reportedUser:id,name', 'reportedProduct:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reports);
    }
}
