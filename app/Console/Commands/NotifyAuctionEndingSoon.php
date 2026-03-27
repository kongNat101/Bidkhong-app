<?php

namespace App\Console\Commands;

use App\Models\Bid;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductWatch;
use Illuminate\Console\Command;

class NotifyAuctionEndingSoon extends Command
{
    protected $signature = 'auctions:notify-ending-soon';
    protected $description = 'Send notifications to bidders when auctions are ending soon (1h, 15m) and notify watchers when auctions start';

    public function handle()
    {
        $this->notifyEndingIn60Minutes();
        $this->notifyEndingIn15Minutes();
        $this->notifyWatchersAuctionStarted();
        $this->notifySellerAuctionStarted();
    }

    // แจ้งเตือน 1 ชั่วโมงสุดท้าย
    private function notifyEndingIn60Minutes(): void
    {
        $products = Product::where('status', 'active')
            ->where('auction_end_time', '>', now())
            ->where('auction_end_time', '<=', now()->addMinutes(60))
            ->get();

        foreach ($products as $product) {
            $this->notifyBidders($product, 'auction_ending_1h', 'เหลือเวลาอีก 1 ชั่วโมง!', "การประมูล {$product->name} จะสิ้นสุดในอีก 1 ชั่วโมง! อย่าพลาดโอกาสสุดท้าย!");
        }
    }

    // แจ้งเตือน 15 นาทีสุดท้าย
    private function notifyEndingIn15Minutes(): void
    {
        $products = Product::where('status', 'active')
            ->where('auction_end_time', '>', now())
            ->where('auction_end_time', '<=', now()->addMinutes(15))
            ->get();

        foreach ($products as $product) {
            $this->notifyBidders($product, 'auction_ending_15m', 'เหลือเวลาอีก 15 นาที!', "การประมูล {$product->name} จะสิ้นสุดในอีก 15 นาที! รีบเสนอราคาก่อนหมดเวลา!");
        }
    }

    // แจ้ง watchers เมื่อสินค้าเพิ่งเปิดประมูล (auction_start_time เพิ่งผ่าน)
    private function notifyWatchersAuctionStarted(): void
    {
        $recentlyStarted = Product::where('status', 'active')
            ->where('auction_start_time', '<=', now())
            ->where('auction_start_time', '>=', now()->subMinutes(2))
            ->get();

        foreach ($recentlyStarted as $product) {
            $watcherIds = ProductWatch::where('product_id', $product->id)->pluck('user_id');

            $alreadyNotifiedIds = Notification::where('product_id', $product->id)
                ->where('type', 'watched_auction_started')
                ->whereIn('user_id', $watcherIds)
                ->pluck('user_id');

            $toNotify = $watcherIds->diff($alreadyNotifiedIds);

            foreach ($toNotify as $watcherId) {
                if ($watcherId !== $product->user_id) {
                    Notification::create([
                        'user_id' => $watcherId,
                        'type' => 'watched_auction_started',
                        'title' => 'สินค้าที่คุณติดตามเปิดประมูลแล้ว!',
                        'message' => "การประมูล {$product->name} เริ่มแล้ว! เข้าไปเสนอราคาเลย!",
                        'product_id' => $product->id,
                    ]);
                }
            }
        }
    }

    // แจ้ง seller เมื่อสินค้าเริ่มประมูลจริง (auction_start_time เพิ่งผ่าน)
    private function notifySellerAuctionStarted(): void
    {
        $recentlyStarted = Product::where('status', 'active')
            ->where('auction_start_time', '<=', now())
            ->where('auction_start_time', '>=', now()->subMinutes(2))
            ->get();

        foreach ($recentlyStarted as $product) {
            $alreadyNotified = Notification::where('product_id', $product->id)
                ->where('user_id', $product->user_id)
                ->where('type', 'seller_auction_started')
                ->exists();

            if (!$alreadyNotified) {
                Notification::create([
                    'user_id' => $product->user_id,
                    'type' => 'seller_auction_started',
                    'title' => 'สินค้าของคุณเริ่มประมูลแล้ว!',
                    'message' => "การประมูล {$product->name} เริ่มต้นแล้ว! รอติดตามผลการประมูลได้เลย",
                    'product_id' => $product->id,
                ]);
            }
        }
    }

    // Helper: แจ้งเตือน bidders ทุกคนที่เคย bid สินค้านี้ (ป้องกันซ้ำ)
    private function notifyBidders(Product $product, string $type, string $title, string $message): void
    {
        $bidderIds = Bid::where('product_id', $product->id)
            ->distinct()
            ->pluck('user_id');

        $alreadyNotifiedIds = Notification::where('product_id', $product->id)
            ->where('type', $type)
            ->whereIn('user_id', $bidderIds)
            ->pluck('user_id');

        $toNotify = $bidderIds->diff($alreadyNotifiedIds);

        foreach ($toNotify as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'product_id' => $product->id,
            ]);
        }
    }
}
