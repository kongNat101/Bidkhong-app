<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Bid;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // GET /api/users/me/orders - ดู orders ของตัวเอง
    public function myOrders(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    // POST /api/products/{id}/close - ปิดประมูลและสร้าง order
    public function closeAuction(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        // Bug 4: ตรวจสอบว่าเป็นเจ้าของสินค้า
        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Only the product owner can close this auction'
            ], 403);
        }

        // Bug 9: เช็คว่าสินค้ายัง active อยู่ (ป้องกัน close ซ้ำ)
        if ($product->status !== 'active') {
            return response()->json([
                'message' => 'This auction has already been closed'
            ], 400);
        }

        // เช็คว่าประมูลหมดเวลาแล้วหรือยัง
        if ($product->auction_end_time > now()) {
            return response()->json([
                'message' => 'Auction has not ended yet',
                'ends_at' => $product->auction_end_time
            ], 400);
        }

        // เช็คว่ามีคนประมูลหรือไม่
        $winningBid = Bid::where('product_id', $productId)
            ->where('status', 'active')
            ->orderBy('price', 'desc')
            ->first();

        if (!$winningBid) {
            // ไม่มีคนประมูล → เปลี่ยนสถานะเป็น cancelled
            $product->update(['status' => 'cancelled']);
            return response()->json([
                'message' => 'No bids found. Auction cancelled.'
            ], 400);
        }

        // Bug 3: สร้าง order พร้อม notification — ไม่โอนเงินทันที (Escrow Flow)
        DB::transaction(function () use ($product, $winningBid) {
            // อัพเดทสถานะ bid ผู้ชนะ
            $winningBid->update(['status' => 'won']);

            // อัพเดท bids ที่แพ้ + ส่ง notification
            $losingBids = Bid::where('product_id', $product->id)
                ->where('id', '!=', $winningBid->id)
                ->whereIn('status', ['outbid', 'active'])
                ->get();

            foreach ($losingBids as $losingBid) {
                $losingBid->update(['status' => 'lost']);

                \App\Models\Notification::create([
                    'user_id' => $losingBid->user_id,
                    'type' => 'lost',
                    'title' => 'Auction ended',
                    'message' => "The auction for {$product->name} has ended. You did not win.",
                    'product_id' => $product->id,
                ]);
            }

            // สร้าง order — status = pending_buyer_confirm (รอ Buyer confirm)
            Order::create([
                'user_id' => $winningBid->user_id,
                'seller_id' => $product->user_id,
                'product_id' => $product->id,
                'final_price' => $winningBid->price,
                'status' => 'pending_buyer_confirm',
                'confirm_deadline' => now()->addHours(48),
            ]);

            // ส่ง notification ให้ผู้ชนะ — confirm ภายใน 48 ชม. + ดู contact Seller ได้เลย
            \App\Models\Notification::create([
                'user_id' => $winningBid->user_id,
                'type' => 'won',
                'title' => 'Congratulations! You won!',
                'message' => "You won the auction for {$product->name} at " . number_format($winningBid->price) . " Baht! Please confirm the order within 48 hours. You can view the seller's contact info now.",
                'product_id' => $product->id,
            ]);

            // ส่ง notification ให้ผู้ขาย — สินค้าขายแล้ว + ดู contact Buyer ได้เลย
            \App\Models\Notification::create([
                'user_id' => $product->user_id,
                'type' => 'sold',
                'title' => 'Your item has been sold!',
                'message' => "Your {$product->name} has been sold for " . number_format($winningBid->price) . " Baht! You can view the buyer's contact info now.",
                'product_id' => $product->id,
            ]);

            // อัพเดทสถานะสินค้า
            $product->update(['status' => 'completed']);
        });

        return response()->json([
            'message' => 'Auction closed successfully. Buyer needs to confirm within 48 hours.',
            'winner_id' => $winningBid->user_id,
            'final_price' => $winningBid->price
        ]);
    }

}