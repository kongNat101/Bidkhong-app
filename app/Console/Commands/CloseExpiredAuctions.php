<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Bid;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class CloseExpiredAuctions extends Command
{
    protected $signature = 'auctions:close-expired';
    protected $description = 'Close all expired auctions and create orders for winners';
    public function handle()
    {
        // หาสินค้าที่หมดเวลาและยังไม่ได้ปิด
        $expiredProducts = Product::where('auction_end_time', '<=', now())
            ->where('status', 'active')
            ->get();

        if ($expiredProducts->isEmpty()) {
            $this->info('No expired auctions found.');
            return 0;
        }

        $closedCount = 0;

        foreach ($expiredProducts as $product) {
            // หา bid ที่ชนะ
            $winningBid = Bid::where('product_id', $product->id)
                ->where('status', 'active')
                ->orderBy('price', 'desc')
                ->first();

            if (!$winningBid) {
                // ไม่มีคนประมูล -> เปลี่ยนสถานะเป็น cancelled
                $product->update(['status' => 'cancelled']);

                // แจ้ง seller ว่าประมูลจบแล้วแต่ไม่มีคน bid
                \App\Models\Notification::create([
                    'user_id' => $product->user_id,
                    'type' => 'auction_ended_no_bids',
                    'title' => 'การประมูลสิ้นสุดแล้ว',
                    'message' => "การประมูล {$product->name} สิ้นสุดแล้วโดยไม่มีผู้เสนอราคา",
                    'product_id' => $product->id,
                ]);

                $this->warn("Product #{$product->id} ({$product->name}) - No bids, marked as cancelled");
                continue;
            }

            // ปิดประมูลและสร้าง order
            DB::transaction(function () use ($product, $winningBid) {
                // Lock product เพื่อป้องกัน duplicate close
                $product = Product::lockForUpdate()->find($product->id);
                if ($product->status !== 'active') return;

                // อัพเดทสถานะ bid
                $winningBid->update(['status' => 'won']);

                // ไม่แตะ wallet — เงิน buyer ยังอยู่ใน balance_pending จากตอน bid
                // เงินจะถูกจัดการใน PostAuctionController:
                // confirm() → escrow hold, receive() → releaseEscrow() → โอนให้ seller

                // ส่ง notification ให้ผู้ชนะ
                \App\Models\Notification::create([
                    'user_id' => $winningBid->user_id,
                    'type' => 'won',
                    'title' => 'Congratulations! You won! 🎉',
                    'message' => "You won the auction for {$product->name} at " . number_format($winningBid->price) . " Baht!",
                    'product_id' => $product->id,
                ]);

                // ส่ง notification ให้ผู้ขาย
                \App\Models\Notification::create([
                    'user_id' => $product->user_id,
                    'type' => 'sold',
                    'title' => 'Your item has been sold! 💰',
                    'message' => "Your {$product->name} has been sold for " . number_format($winningBid->price) . " Baht!",
                    'product_id' => $product->id,
                ]);

                // อัพเดท bids ที่แพ้
                $losingBids = Bid::where('product_id', $product->id)
                    ->where('id', '!=', $winningBid->id)
                    ->whereIn('status', ['outbid', 'active'])
                    ->get();

                foreach ($losingBids as $losingBid) {
                    $losingBid->update(['status' => 'lost']);

                    // ส่ง notification ให้ผู้แพ้
                    \App\Models\Notification::create([
                        'user_id' => $losingBid->user_id,
                        'type' => 'lost',
                        'title' => 'Auction ended',
                        'message' => "The auction for {$product->name} has ended. You did not win.",
                        'product_id' => $product->id,
                    ]);
                }

                // สร้าง order — status = pending_buyer_confirm (รอ Buyer confirm)
                \App\Models\Order::create([
                    'user_id' => $winningBid->user_id,
                    'seller_id' => $product->user_id,
                    'product_id' => $product->id,
                    'final_price' => $winningBid->price,
                    'status' => 'pending_buyer_confirm',
                    'confirm_deadline' => now()->addHours(48),
                ]);

                // อัพเดทสถานะสินค้า
                $product->update(['status' => 'completed']);
            });

            $this->info("Product #{$product->id} ({$product->name}) - Closed. Winner: User #{$winningBid->user_id}, Price: {$winningBid->price}");
            $closedCount++;
        }

        $this->info("Total auctions closed: {$closedCount}");
        return 0;
    }
}