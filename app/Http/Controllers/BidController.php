<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\Product;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BidController extends Controller
{
    // POST /api/products/{id}/bid - ประมูลสินค้า
    public function bid(Request $request, $productId)
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $product = Product::findOrFail($productId);
        $user = $request->user();

        // เช็คว่าสินค้ายัง active อยู่
        if ($product->status !== 'active') {
            return response()->json([
                'message' => 'This auction is no longer active'
            ], 400);
        }

        // เช็คว่า auction ยังไม่หมดเวลา
        if ($product->auction_end_time < now()) {
            return response()->json([
                'message' => 'Auction has ended'
            ], 400);
        }

        // เช็คว่าไม่ใช่เจ้าของสินค้า
        if ($product->user_id === $user->id) {
            return response()->json([
                'message' => 'You cannot bid on your own product'
            ], 400);
        }

        // เช็คว่าราคาสูงกว่า current_price
        if ($validated['price'] <= $product->current_price) {
            return response()->json([
                'message' => 'Bid must be higher than current price',
                'current_price' => $product->current_price
            ], 400);
        }

        // เช็ค bid increment (จำนวนบิดขั้นต่ำ)
        $bidIncrement = $product->bid_increment ?? 1;
        $minimumBid = $product->current_price + $bidIncrement;
        if ($validated['price'] < $minimumBid) {
            return response()->json([
                'message' => 'Bid must be at least ' . number_format($bidIncrement) . ' Baht higher than current price',
                'current_price' => $product->current_price,
                'bid_increment' => $bidIncrement,
                'minimum_bid' => $minimumBid,
            ], 400);
        }

        // เช็คว่า user มีเงินพอ
        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance_available < $validated['price']) {
            return response()->json([
                'message' => 'Insufficient balance'
            ], 400);
        }

        DB::transaction(function () use ($product, $user, $validated, $wallet) {
            // หา bid เก่าที่ active
            $previousBids = Bid::where('product_id', $product->id)
                ->where('status', 'active')
                ->get();

            // อัพเดท bids เก่าเป็น 'outbid' และส่ง notification + refund
            foreach ($previousBids as $oldBid) {
                $oldBid->update(['status' => 'outbid']);

                // Refund เงินให้คนที่ถูกประมูลทับ
                $oldBidWallet = $oldBid->user->wallet;
                if ($oldBidWallet) {
                    $oldBidWallet->balance_available += $oldBid->price;
                    $oldBidWallet->balance_pending -= $oldBid->price;
                    $oldBidWallet->save();

                    // บันทึก refund transaction
                    WalletTransaction::create([
                        'user_id' => $oldBid->user_id,
                        'wallet_id' => $oldBidWallet->id,
                        'type' => 'bid_refund',
                        'amount' => $oldBid->price,
                        'description' => "Refund - Outbid on {$product->name}",
                        'reference_type' => 'product',
                        'reference_id' => $product->id,
                        'balance_after' => $oldBidWallet->balance_available,
                    ]);
                }

                // ส่ง notification ให้คนที่ถูกประมูลทับ
                \App\Models\Notification::create([
                    'user_id' => $oldBid->user_id,
                    'type' => 'outbid',
                    'title' => 'มีผู้เสนอราคาสูงกว่าคุณ!',
                    'message' => "มีคนเสนอราคา " . number_format($validated['price']) . " บาท สำหรับ {$product->name} สูงกว่าคุณ! เงินของคุณถูกคืนแล้ว กลับไปเสนอราคาใหม่เลย!",
                    'product_id' => $product->id,
                ]);
            }

            // โหลด wallet ใหม่จาก DB (กรณี bid สินค้าเดิมซ้ำ — refund ด้านบนอาจแก้ wallet เดียวกัน)
            $wallet->refresh();

            // หักเงินจาก wallet
            $wallet->balance_available -= $validated['price'];
            $wallet->balance_pending += $validated['price'];
            $wallet->save();

            // บันทึก bid transaction
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'bid_placed',
                'amount' => -$validated['price'],
                'description' => "Bid on {$product->name}",
                'reference_type' => 'product',
                'reference_id' => $product->id,
                'balance_after' => $wallet->balance_available,
            ]);

            // สร้าง bid ใหม่
            Bid::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'price' => $validated['price'],
                'status' => 'active',
            ]);

            // อัพเดท current_price
            $product->update([
                'current_price' => $validated['price']
            ]);

            // Anti-sniping: ถ้า bid ในช่วง 5 นาทีสุดท้าย → รีเซ็ตเวลากลับไปเหลือ 5 นาที
            $minutesLeft = now()->diffInMinutes($product->auction_end_time, false);
            if ($minutesLeft >= 0 && $minutesLeft <= 5) {
                $product->auction_end_time = now()->addMinutes(5);
                $product->save();
            }
        });

        // โหลดเวลาใหม่ (อาจถูก anti-sniping ขยายเวลา)
        $product->refresh();

        return response()->json([
            'message' => 'Bid placed successfully',
            'current_price' => $validated['price'],
            'auction_end_time' => $product->auction_end_time,
        ], 201);
    }

    // GET /api/products/{id}/bids - ดูประวัติการประมูลของสินค้า
    public function getProductBids($productId)
    {
        $bids = Bid::where('product_id', $productId)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bids);
    }

    // GET /api/users/me/bids - ดูประวัติการประมูลของตัวเอง (พร้อม summary + filter)
    public function getUserBids(Request $request)
    {
        $userId = $request->user()->id;

        // ดึง bids ทั้งหมดของ user พร้อม product
        $allBids = Bid::where('user_id', $userId)
            ->with('product:id,name,current_price,auction_end_time,status,picture')
            ->orderBy('created_at', 'desc')
            ->get();

        // คำนวณ status ของแต่ละ bid
        $bidsWithStatus = $allBids->map(function ($bid) use ($userId) {
            $product = $bid->product;

            if (!$product) {
                $bid->bid_status = 'unknown';
                return $bid;
            }

            if ($product->status === 'sold' || $product->status === 'closed') {
                // ประมูลจบแล้ว — ดูว่าใครชนะ
                $highestBid = Bid::where('product_id', $product->id)
                    ->orderBy('price', 'desc')
                    ->first();

                $bid->bid_status = ($highestBid && $highestBid->user_id === $userId) ? 'won' : 'lost';
            }
            else {
                // ยังประมูลอยู่ — ดูว่า bid นี้สูงสุดไหม
                $highestBid = Bid::where('product_id', $product->id)
                    ->orderBy('price', 'desc')
                    ->first();

                $bid->bid_status = ($highestBid && $highestBid->user_id === $userId) ? 'winning' : 'outbid';
            }

            return $bid;
        });

        // นับ summary
        $summary = [
            'total' => $bidsWithStatus->count(),
            'winning' => $bidsWithStatus->where('bid_status', 'winning')->count(),
            'outbid' => $bidsWithStatus->where('bid_status', 'outbid')->count(),
            'won' => $bidsWithStatus->where('bid_status', 'won')->count(),
            'lost' => $bidsWithStatus->where('bid_status', 'lost')->count(),
        ];

        // Filter ตาม status (ถ้ามี)
        $filtered = $bidsWithStatus;
        if ($request->status && in_array($request->status, ['winning', 'outbid', 'won', 'lost'])) {
            $filtered = $bidsWithStatus->where('bid_status', $request->status)->values();
        }

        return response()->json([
            'summary' => $summary,
            'bids' => $filtered,
        ]);
    }

    // POST /api/products/{id}/buy-now - ซื้อทันทีในราคา Buyout
    public function buyNow(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $user = $request->user();

        // เช็คว่ามี buyout_price หรือไม่
        if (!$product->buyout_price) {
            return response()->json([
                'message' => 'This product does not support Buy Now'
            ], 400);
        }

        // เช็คว่า auction ยังไม่หมดเวลา
        if ($product->auction_end_time < now()) {
            return response()->json([
                'message' => 'Auction has ended'
            ], 400);
        }

        // เช็คว่าสินค้ายัง active
        if ($product->status !== 'active') {
            return response()->json([
                'message' => 'This auction is no longer active'
            ], 400);
        }

        // เช็คว่าไม่ใช่เจ้าของสินค้า
        if ($product->user_id === $user->id) {
            return response()->json([
                'message' => 'You cannot buy your own product'
            ], 400);
        }

        // เช็คว่า user มีเงินพอ
        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance_available < $product->buyout_price) {
            return response()->json([
                'message' => 'Insufficient balance',
                'required' => $product->buyout_price,
                'available' => $wallet ? $wallet->balance_available : 0,
            ], 400);
        }

        DB::transaction(function () use ($product, $user, $wallet) {
            // Refund all existing active bids
            $activeBids = Bid::where('product_id', $product->id)
                ->where('status', 'active')
                ->get();

            foreach ($activeBids as $oldBid) {
                $oldBid->update(['status' => 'lost']);

                // Refund เงินให้คนที่ประมูลอยู่
                $oldBidWallet = $oldBid->user->wallet;
                if ($oldBidWallet) {
                    $oldBidWallet->balance_available += $oldBid->price;
                    $oldBidWallet->balance_pending -= $oldBid->price;
                    $oldBidWallet->save();

                    // บันทึก refund transaction
                    WalletTransaction::create([
                        'user_id' => $oldBid->user_id,
                        'wallet_id' => $oldBidWallet->id,
                        'type' => 'bid_refund',
                        'amount' => $oldBid->price,
                        'description' => "Refund - {$product->name} sold via Buy Now",
                        'reference_type' => 'product',
                        'reference_id' => $product->id,
                        'balance_after' => $oldBidWallet->balance_available,
                    ]);
                }

                // ส่ง notification ให้คนที่ประมูลอยู่
                \App\Models\Notification::create([
                    'user_id' => $oldBid->user_id,
                    'type' => 'lost',
                    'title' => 'Auction ended - Item sold',
                    'message' => "{$product->name} has been purchased via Buy Now. Your bid has been refunded.",
                    'product_id' => $product->id,
                ]);
            }

            // หักเงินจาก buyer wallet
            $wallet->balance_available -= $product->buyout_price;
            $wallet->balance_total -= $product->buyout_price;
            $wallet->save();

            // บันทึก transaction ของ buyer
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'auction_won',
                'amount' => -$product->buyout_price,
                'description' => "Buy Now: {$product->name}",
                'reference_type' => 'product',
                'reference_id' => $product->id,
                'balance_after' => $wallet->balance_available,
            ]);

            // โอนเงินให้ seller
            $sellerWallet = $product->user->wallet;
            if ($sellerWallet) {
                $sellerWallet->balance_available += $product->buyout_price;
                $sellerWallet->balance_total += $product->buyout_price;
                $sellerWallet->save();

                // บันทึก transaction ของ seller
                WalletTransaction::create([
                    'user_id' => $product->user_id,
                    'wallet_id' => $sellerWallet->id,
                    'type' => 'auction_sold',
                    'amount' => $product->buyout_price,
                    'description' => "Sold (Buy Now): {$product->name}",
                    'reference_type' => 'product',
                    'reference_id' => $product->id,
                    'balance_after' => $sellerWallet->balance_available,
                ]);
            }

            // สร้าง bid record สำหรับ buy now
            Bid::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'price' => $product->buyout_price,
                'status' => 'won',
            ]);

            // สร้าง Order — status = pending_buyer_confirm (รอ Buyer confirm)
            \App\Models\Order::create([
                'user_id' => $user->id,
                'seller_id' => $product->user_id,
                'product_id' => $product->id,
                'final_price' => $product->buyout_price,
                'status' => 'pending_buyer_confirm',
                'confirm_deadline' => now()->addHours(48),
            ]);

            // อัพเดทสถานะสินค้า
            $product->update([
                'current_price' => $product->buyout_price,
                'status' => 'completed',
            ]);

            // ส่ง notification ให้ buyer
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'type' => 'won',
                'title' => 'Purchase successful! 🎉',
                'message' => "You bought {$product->name} for " . number_format($product->buyout_price) . " Baht!",
                'product_id' => $product->id,
            ]);

            // ส่ง notification ให้ seller
            \App\Models\Notification::create([
                'user_id' => $product->user_id,
                'type' => 'sold',
                'title' => 'Your item has been sold! 💰',
                'message' => "Your {$product->name} has been sold via Buy Now for " . number_format($product->buyout_price) . " Baht!",
                'product_id' => $product->id,
            ]);
        });

        return response()->json([
            'message' => 'Purchase successful',
            'product' => $product->fresh(),
        ], 200);
    }
}