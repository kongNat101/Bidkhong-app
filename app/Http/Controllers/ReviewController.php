<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // POST /api/users/{sellerId}/rate — ผู้ซื้อให้คะแนนผู้ขาย (1-5 ดาว)
    public function rate(Request $request, $sellerId)
    {
        $userId = $request->user()->id;

        // เช็คว่า seller มีจริง
        $seller = User::find($sellerId);
        if (!$seller) {
            return response()->json(['message' => 'Seller not found'], 404);
        }

        // ห้าม rate ตัวเอง
        if ($userId == $sellerId) {
            return response()->json(['message' => 'You cannot rate yourself'], 400);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        // เช็คว่า order นี้เป็นของ buyer + seller ตรงกัน
        $order = Order::find($validated['order_id']);

        if ($order->user_id !== $userId) {
            return response()->json([
                'message' => 'You are not the buyer of this order'
            ], 403);
        }

        if ($order->seller_id != $sellerId) {
            return response()->json([
                'message' => 'This order does not belong to this seller'
            ], 400);
        }

        // เช็คว่า order completed แล้ว
        if ($order->status !== 'completed') {
            return response()->json([
                'message' => 'You can only rate after the order is completed',
                'current_status' => $order->status,
            ], 400);
        }

        // เช็คว่ายังไม่เคย rate order นี้
        $existing = Review::where('order_id', $order->id)->first();
        if ($existing) {
            return response()->json([
                'message' => 'You have already rated this order',
            ], 409);
        }

        $review = Review::create([
            'order_id' => $order->id,
            'reviewer_id' => $userId,
            'seller_id' => $sellerId,
            'rating' => $validated['rating'],
        ]);

        // อัปเดต rating + total_reviews ของ seller ใน users table
        $avgRating = Review::where('seller_id', $sellerId)->avg('rating');
        $totalReviews = Review::where('seller_id', $sellerId)->count();
        $seller->update([
            'rating' => round($avgRating, 2),
            'total_reviews' => $totalReviews,
        ]);

        $review->load('reviewer:id,name');

        return response()->json([
            'message' => 'Rating submitted successfully',
            'review' => $review,
            'seller_rating' => (float) $seller->fresh()->rating,
            'seller_total_reviews' => $seller->fresh()->total_reviews,
        ], 201);
    }

    // GET /api/users/{sellerId}/ratings — ดูคะแนนผู้ขาย (public)
    public function getSellerRatings($sellerId)
    {
        $seller = User::find($sellerId);
        if (!$seller) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $reviews = Review::where('seller_id', $sellerId)
            ->with('reviewer:id,name,profile_image')
            ->orderByDesc('created_at')
            ->paginate(20);

        // สรุปคะแนนจาก users table
        $summary = [
            'average_rating' => (float) $seller->rating,
            'total_reviews' => $seller->total_reviews,
            'rating_breakdown' => [
                5 => Review::where('seller_id', $sellerId)->where('rating', 5)->count(),
                4 => Review::where('seller_id', $sellerId)->where('rating', 4)->count(),
                3 => Review::where('seller_id', $sellerId)->where('rating', 3)->count(),
                2 => Review::where('seller_id', $sellerId)->where('rating', 2)->count(),
                1 => Review::where('seller_id', $sellerId)->where('rating', 1)->count(),
            ],
        ];

        return response()->json([
            'summary' => $summary,
            'ratings' => $reviews,
        ]);
    }
}
