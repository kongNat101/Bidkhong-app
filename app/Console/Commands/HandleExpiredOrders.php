<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Bid;
use App\Models\WalletTransaction;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class HandleExpiredOrders extends Command
{
    protected $signature = 'orders:handle-expired';
    protected $description = 'Handle expired confirm and ship deadlines';

    public function handle()
    {
        $this->handleExpiredConfirm();
        $this->handleExpiredShip();
        return 0;
    }

    // ยกเลิก order ที่หมดเวลา confirm (48 ชม.)
    private function handleExpiredConfirm()
    {
        $expiredOrders = Order::where('status', 'pending_buyer_confirm')
            ->where('confirm_deadline', '<=', now())
            ->with(['product', 'user', 'seller'])
            ->get();

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order) {
                $order->status = 'cancelled';
                $order->save();

                // คืนเงินจาก pending ให้ buyer
                $buyerWallet = Wallet::lockForUpdate()->where('user_id', $order->user_id)->first();
                if ($buyerWallet) {
                    // หา bid ที่ชนะ
                    $wonBid = Bid::where('user_id', $order->user_id)
                        ->where('product_id', $order->product_id)
                        ->where('status', 'won')
                        ->first();

                    if ($wonBid) {
                        $buyerWallet->balance_available += $wonBid->price;
                        $buyerWallet->balance_pending -= $wonBid->price;
                        $buyerWallet->save();

                        $wonBid->update(['status' => 'lost']);

                        WalletTransaction::create([
                            'user_id' => $order->user_id,
                            'wallet_id' => $buyerWallet->id,
                            'type' => 'bid_refund',
                            'amount' => $wonBid->price,
                            'description' => "Refund - Confirm deadline expired: {$order->product->name}",
                            'reference_type' => 'order',
                            'reference_id' => $order->id,
                            'balance_after' => $buyerWallet->balance_available,
                        ]);
                    }
                }

                // แจ้ง buyer
                Notification::create([
                    'user_id' => $order->user_id,
                    'type' => 'order',
                    'title' => 'คำสั่งซื้อถูกยกเลิก',
                    'message' => "คำสั่งซื้อ {$order->product->name} ถูกยกเลิกเนื่องจากหมดเวลายืนยัน เงินถูกคืนแล้ว",
                    'product_id' => $order->product_id,
                ]);

                // แจ้ง seller
                Notification::create([
                    'user_id' => $order->seller_id,
                    'type' => 'order',
                    'title' => 'คำสั่งซื้อถูกยกเลิก',
                    'message' => "คำสั่งซื้อ {$order->product->name} ถูกยกเลิกเนื่องจากผู้ซื้อไม่ยืนยันภายในเวลา",
                    'product_id' => $order->product_id,
                ]);
            });

            $this->warn("Order #{$order->id} - Confirm expired, cancelled and refunded");
        }

        if ($expiredOrders->isEmpty()) {
            $this->info('No expired confirm orders found.');
        }
    }

    // ยกเลิก order ที่ seller ไม่ส่งของตามเวลา (3 วัน)
    private function handleExpiredShip()
    {
        $expiredOrders = Order::where('status', 'confirmed')
            ->where('ship_deadline', '<=', now())
            ->with(['product', 'user', 'seller'])
            ->get();

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order) {
                $order->status = 'cancelled';
                $order->save();

                // คืนเงิน escrow ให้ buyer
                $buyerWallet = Wallet::lockForUpdate()->where('user_id', $order->user_id)->first();
                if ($buyerWallet) {
                    $buyerWallet->balance_pending -= $order->final_price;
                    $buyerWallet->balance_available += $order->final_price;
                    $buyerWallet->save();

                    WalletTransaction::create([
                        'user_id' => $order->user_id,
                        'wallet_id' => $buyerWallet->id,
                        'type' => 'escrow_refund',
                        'amount' => $order->final_price,
                        'description' => "Refund - Seller did not ship: {$order->product->name}",
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'balance_after' => $buyerWallet->balance_available,
                    ]);
                }

                // แจ้ง buyer
                Notification::create([
                    'user_id' => $order->user_id,
                    'type' => 'order',
                    'title' => 'คำสั่งซื้อถูกยกเลิก — ผู้ขายไม่จัดส่ง',
                    'message' => "คำสั่งซื้อ {$order->product->name} ถูกยกเลิกเนื่องจากผู้ขายไม่จัดส่งภายในเวลา เงินถูกคืนแล้ว",
                    'product_id' => $order->product_id,
                ]);

                // แจ้ง seller
                Notification::create([
                    'user_id' => $order->seller_id,
                    'type' => 'order',
                    'title' => 'คำสั่งซื้อถูกยกเลิก — หมดเวลาจัดส่ง',
                    'message' => "คำสั่งซื้อ {$order->product->name} ถูกยกเลิกเนื่องจากไม่จัดส่งภายใน 3 วัน",
                    'product_id' => $order->product_id,
                ]);
            });

            $this->warn("Order #{$order->id} - Ship deadline expired, cancelled and refunded");
        }

        if ($expiredOrders->isEmpty()) {
            $this->info('No expired ship orders found.');
        }
    }
}
