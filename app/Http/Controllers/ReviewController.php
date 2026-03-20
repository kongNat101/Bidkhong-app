<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // POST /api/orders/{id}/review — ผู้ซื้อให้คะแนนผู้ขาย (1-5 ดาว)
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
        ]);

        $review = Review::create([
            'order_id' => $order->id,
            'reviewer_id' => $userId,
            'seller_id' => $order->seller_id,
            'rating' => $validated['rating'],
        ]);

        // อัปเดต rating + total_reviews ของ seller ใน users table
        $seller = User::find($order->seller_id);
        if ($seller) {
            $avgRating = Review::where('seller_id', $seller->id)->avg('rating');
            $totalReviews = Review::where('seller_id', $seller->id)->count();
            $seller->update([
                'rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
            ]);
        }

        $review->load('reviewer:id,name');

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review,
        ], 201);
    }

    // GET /api/users/{id}/reviews — ดูรีวิวผู้ขาย (public)
    public function getSellerReviews($userId)
    {
        $seller = User::find($userId);
        if (!$seller) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $reviews = Review::where('seller_id', $userId)
            ->with('reviewer:id,name,profile_image')
            ->orderByDesc('created_at')
            ->paginate(20);

        // สรุปคะแนนจาก users table (ไม่ต้อง compute ใหม่)
        $summary = [
            'average_rating' => (float) $seller->rating,
            'total_reviews' => $seller->total_reviews,
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
