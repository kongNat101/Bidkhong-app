<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Dispute;
use App\Models\UserStrike;
use App\Models\WalletTransaction;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostAuctionController extends Controller
{
    // POST /api/orders/{id}/confirm — Buyer กด confirm + hold เงิน escrow
    public function confirm(Request $request, $id)
    {
        $order = Order::with('product')->findOrFail($id);
        $userId = $request->user()->id;

        // เฉพาะ Buyer เท่านั้นที่ confirm ได้
        if ($order->user_id !== $userId) {
            return response()->json([
                'message' => 'Only the buyer can confirm this order'
            ], 403);
        }

        // เช็คว่า order อยู่ใน status pending_buyer_confirm
        if ($order->status !== 'pending_buyer_confirm') {
            return response()->json([
                'message' => 'This order is not awaiting confirmation',
                'current_status' => $order->status
            ], 400);
        }

        // เช็คว่าหมดเวลา confirm หรือยัง
        if ($order->isConfirmExpired()) {
            return response()->json([
                'message' => 'Confirmation deadline has passed'
            ], 400);
        }

        // เช็คว่า user ถูกแบนหรือไม่
        $activeBan = UserStrike::where('user_id', $userId)
            ->where('banned_until', '>', now())
            ->first();

        if ($activeBan) {
            return response()->json([
                'message' => 'You are temporarily banned from transactions',
                'banned_until' => $activeBan->banned_until
            ], 403);
        }

        // เช็คว่า confirm ไปแล้วหรือยัง
        if ($order->buyer_confirmed_at) {
            return response()->json([
                'message' => 'You have already confirmed this order'
            ], 400);
        }

        DB::transaction(function () use ($order) {
            $order->buyer_confirmed_at = now();
            $order->status = 'confirmed';
            $order->ship_deadline = now()->addDays(3);
            $order->save();

            // Hold เงินจาก wallet ผู้ชนะ (escrow)
            $buyerWallet = $order->user->wallet;
            if ($buyerWallet) {
                // เช็คว่ามีเงินพอ
                if ($buyerWallet->balance_available < $order->final_price) {
                    // ไม่พอ → ยกเลิก order
                    $order->status = 'cancelled';
                    $order->save();

                    Notification::create([
                        'user_id' => $order->user_id,
                        'type' => 'order',
                        'title' => 'Order cancelled — insufficient funds',
                        'message' => "Your order for {$order->product->name} was cancelled due to insufficient wallet balance.",
                        'product_id' => $order->product_id,
                    ]);
                    Notification::create([
                        'user_id' => $order->seller_id,
                        'type' => 'order',
                        'title' => 'Order cancelled',
                        'message' => "The order for {$order->product->name} was cancelled because the buyer has insufficient funds.",
                        'product_id' => $order->product_id,
                    ]);
                    return;
                }

                $buyerWallet->balance_available -= $order->final_price;
                $buyerWallet->balance_pending += $order->final_price;
                $buyerWallet->save();

                WalletTransaction::create([
                    'user_id' => $order->user_id,
                    'wallet_id' => $buyerWallet->id,
                    'type' => 'escrow_hold',
                    'amount' => -$order->final_price,
                    'description' => "Escrow hold: {$order->product->name}",
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'balance_after' => $buyerWallet->balance_available,
                ]);
            }

            // แจ้งเตือน Seller ว่า Buyer confirm แล้ว ให้ส่งของ
            Notification::create([
                'user_id' => $order->seller_id,
                'type' => 'order',
                'title' => 'Buyer confirmed! Please ship the item',
                'message' => "The buyer has confirmed the order for {$order->product->name}. Please ship the item within 3 days.",
                'product_id' => $order->product_id,
            ]);
        });

        $order->refresh();

        return response()->json([
            'message' => 'Order confirmed successfully',
            'order_status' => $order->status,
        ]);
    }

    // GET /api/orders/{id}/detail — ดูรายละเอียด order + contact ทั้ง 2 ฝั่ง (เปิดเผยทันที)
    public function detail(Request $request, $id)
    {
        $order = Order::with(['product', 'user:id,name,phone_number', 'seller:id,name,phone_number', 'dispute'])
            ->findOrFail($id);
        $userId = $request->user()->id;

        // เช็คว่าเป็น buyer หรือ seller
        if ($order->user_id !== $userId && $order->seller_id !== $userId) {
            return response()->json([
                'message' => 'You are not part of this order'
            ], 403);
        }

        $response = [
            'order' => $order,
            'my_role' => $order->user_id === $userId ? 'buyer' : 'seller',
            'buyer_contact' => [
                'name' => $order->user->name,
                'phone_number' => $order->user->phone_number,
            ],
            'seller_contact' => [
                'name' => $order->seller->name,
                'phone_number' => $order->seller->phone_number,
            ],
        ];

        return response()->json($response);
    }

    // POST /api/orders/{id}/ship — ผู้ขายกดจัดส่ง
    public function ship(Request $request, $id)
    {
        $order = Order::with('product')->findOrFail($id);
        $userId = $request->user()->id;

        // เช็คว่าเป็นผู้ขาย
        if ($order->seller_id !== $userId) {
            return response()->json([
                'message' => 'Only the seller can mark as shipped'
            ], 403);
        }

        // เช็คว่า status = confirmed
        if ($order->status !== 'confirmed') {
            return response()->json([
                'message' => 'Order must be in confirmed status to ship',
                'current_status' => $order->status
            ], 400);
        }

        DB::transaction(function () use ($order) {
            $order->status = 'shipped';
            $order->shipped_at = now();
            $order->receive_deadline = now()->addDays(7);
            $order->save();

            // แจ้งเตือนผู้ชนะ
            Notification::create([
                'user_id' => $order->user_id,
                'type' => 'order',
                'title' => 'Item shipped! 📦',
                'message' => "The seller has shipped {$order->product->name}. Please confirm receipt within 7 days.",
                'product_id' => $order->product_id,
            ]);
        });

        return response()->json([
            'message' => 'Order marked as shipped',
            'receive_deadline' => $order->receive_deadline,
        ]);
    }

    // POST /api/orders/{id}/receive — ผู้ชนะกดรับสินค้า → โอนเงินให้ผู้ขาย
    public function receive(Request $request, $id)
    {
        $order = Order::with(['product', 'user', 'seller'])->findOrFail($id);
        $userId = $request->user()->id;

        // เช็คว่าเป็น buyer
        if ($order->user_id !== $userId) {
            return response()->json([
                'message' => 'Only the buyer can confirm receipt'
            ], 403);
        }

        // เช็คว่า status = shipped
        if ($order->status !== 'shipped') {
            return response()->json([
                'message' => 'Order must be in shipped status',
                'current_status' => $order->status
            ], 400);
        }

        DB::transaction(function () use ($order) {
            // อัปเดท order
            $order->status = 'completed';
            $order->received_at = now();
            $order->o_verified = true;
            $order->save();

            // โอนเงินจาก escrow ให้ผู้ขาย
            $this->releaseEscrow($order);
        });

        return response()->json([
            'message' => 'Order completed! Payment released to seller.',
        ]);
    }

    // POST /api/orders/{id}/dispute — ผู้ชนะแจ้งปัญหา
    public function dispute(Request $request, $id)
    {
        $order = Order::with('product')->findOrFail($id);
        $userId = $request->user()->id;

        // เช็คว่าเป็น buyer
        if ($order->user_id !== $userId) {
            return response()->json([
                'message' => 'Only the buyer can file a dispute'
            ], 403);
        }

        // เช็คว่า status = shipped
        if ($order->status !== 'shipped') {
            return response()->json([
                'message' => 'Can only dispute orders that have been shipped',
                'current_status' => $order->status
            ], 400);
        }

        // เช็คว่ายังไม่มี dispute
        $existing = Dispute::where('order_id', $id)->first();
        if ($existing) {
            return response()->json([
                'message' => 'A dispute has already been filed for this order'
            ], 400);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'evidence_images' => ['nullable', 'array', 'max:5'],
            'evidence_images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $imagePaths = [];

        // อัปโหลดรูปหลักฐาน
        if ($request->hasFile('evidence_images')) {
            foreach ($request->file('evidence_images') as $image) {
                $path = $image->store('disputes', 'public');
                $imagePaths[] = $path;
            }
        }

        DB::transaction(function () use ($order, $userId, $validated, $imagePaths) {
            // สร้าง dispute
            Dispute::create([
                'order_id' => $order->id,
                'reporter_id' => $userId,
                'reason' => $validated['reason'],
                'evidence_images' => $imagePaths ?: null,
                'status' => 'open',
            ]);

            // อัปเดท order status
            $order->status = 'disputed';
            $order->save();

            // แจ้งเตือนผู้ขาย
            Notification::create([
                'user_id' => $order->seller_id,
                'type' => 'order',
                'title' => 'Dispute filed ⚠️',
                'message' => "The buyer has filed a dispute for {$order->product->name}. An admin will review the case.",
                'product_id' => $order->product_id,
            ]);
        });

        return response()->json([
            'message' => 'Dispute filed successfully. An admin will review your case.',
        ]);
    }

    // === Helper: โอนเงินจาก escrow ให้ผู้ขาย ===
    private function releaseEscrow(Order $order): void
    {
        // หัก pending จาก buyer
        $buyerWallet = $order->user->wallet;
        if ($buyerWallet) {
            $buyerWallet->balance_pending -= $order->final_price;
            $buyerWallet->balance_total -= $order->final_price;
            $buyerWallet->save();

            WalletTransaction::create([
                'user_id' => $order->user_id,
                'wallet_id' => $buyerWallet->id,
                'type' => 'escrow_release',
                'amount' => -$order->final_price,
                'description' => "Payment released: {$order->product->name}",
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'balance_after' => $buyerWallet->balance_available,
            ]);
        }

        // โอนเข้า wallet ผู้ขาย
        $sellerWallet = $order->seller->wallet;
        if ($sellerWallet) {
            $sellerWallet->balance_available += $order->final_price;
            $sellerWallet->balance_total += $order->final_price;
            $sellerWallet->save();

            WalletTransaction::create([
                'user_id' => $order->seller_id,
                'wallet_id' => $sellerWallet->id,
                'type' => 'auction_sold',
                'amount' => $order->final_price,
                'description' => "Sold: {$order->product->name}",
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'balance_after' => $sellerWallet->balance_available,
            ]);
        }

        // แจ้งเตือนทั้ง 2 ฝั่ง
        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order',
            'title' => 'Order completed ✅',
            'message' => "Your order for {$order->product->name} is complete!",
            'product_id' => $order->product_id,
        ]);
        Notification::create([
            'user_id' => $order->seller_id,
            'type' => 'order',
            'title' => 'Payment received! 💰',
            'message' => "You've received " . number_format($order->final_price) . " Baht for {$order->product->name}.",
            'product_id' => $order->product_id,
        ]);
    }
}