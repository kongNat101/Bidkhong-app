<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // POST /api/orders/{id}/review — ผู้ซื้อให้รีวิวผู้ขาย
    public function store(Request $request, $id)
    {
        $order = Order::with('product')->findOrFail($id);
        $userId = $request->user()->id;

        // เช็คว่าเป็น buyer ของ order นี้
        if ($order->user_id !== $userId) {
            return response()->json([
                'message' => 'Only the buyer can review the seller'
            ], 403);
        }

        // เช็คว่า order completed แล้ว
        if ($order->status !== 'completed') {
            return response()->json([
                'message' => 'You can only review after the order is completed',
                'current_status' => $order->status,
            ], 400);
        }

        // เช็คว่ายังไม่เคย review
        $existing = Review::where('order_id', $order->id)->first();
        if ($existing) {
            return response()->json([
                'message' => 'You have already reviewed this order',
            ], 409);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = Review::create([
            'order_id' => $order->id,
            'reviewer_id' => $userId,
            'seller_id' => $order->seller_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        // โหลด reviewer info
        $review->load('reviewer:id,name');

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review,
        ], 201);
    }

    // GET /api/users/{id}/reviews — ดูรีวิวผู้ขาย (public)
    public function getSellerReviews($userId)
    {
        $reviews = Review::where('seller_id', $userId)
            ->with('reviewer:id,name,profile_image')
            ->orderByDesc('created_at')
            ->paginate(20);

        // สรุปคะแนน
        $summary = [
            'average_rating' => round(Review::where('seller_id', $userId)->avg('rating'), 1) ?: null,
            'total_reviews' => Review::where('seller_id', $userId)->count(),
            'rating_breakdown' => [
                5 => Review::where('seller_id', $userId)->where('rating', 5)->count(),
                4 => Review::where('seller_id', $userId)->where('rating', 4)->count(),
                3 => Review::where('seller_id', $userId)->where('rating', 3)->count(),
                2 => Review::where('seller_id', $userId)->where('rating', 2)->count(),
                1 => Review::where('seller_id', $userId)->where('rating', 1)->count(),
            ],
        ];

        return response()->json([
            'summary' => $summary,
            'reviews' => $reviews,
        ]);
    }
}