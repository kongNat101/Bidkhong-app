<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Report;
use App\Models\UserStrike;
use App\Models\Notification;
use App\Models\ProductCertificate;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    // GET /api/admin/dashboard - สถิติรวม
    public function dashboard(Request $request)
    {
        return response()->json([
            'total_users' => User::where('role', 'user')->count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'active_auctions' => Product::where('status', 'active')->count(),
            'open_disputes' => Report::where('type', 'dispute')->where('status', 'open')->count(),
            'pending_reports' => Report::where('type', '!=', 'dispute')->where('status', 'pending')->count(),
            'pending_products' => Product::where('status', 'pending')->count(),
            'pending_certificates' => ProductCertificate::where('status', 'pending')->count(),
            'recent_orders' => Order::with(['product:id,name', 'user:id,name', 'seller:id,name'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get(),
        ]);
    }

    // GET /api/admin/reports - ดู reports + disputes ทั้งหมด (รวมเป็นระบบเดียว)
    public function reports(Request $request)
    {
        $query = Report::with([
            'reporter:id,name,email',
            'reportedUser:id,name,email',
            'reportedProduct:id,name',
            'order.product:id,name',
            'order.user:id,name',
            'order.seller:id,name',
            'repliedBy:id,name',
        ]);

        // กรองตาม status (?status=pending)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // กรองตาม type (?type=dispute)
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $reports = $query->orderBy('created_at', 'desc')->get();

        return response()->json($reports);
    }

    // PATCH /api/admin/reports/{id} - อัปเดต report หรือ resolve dispute
    public function updateReport(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        if ($report->isDispute()) {
            return $this->handleDisputeUpdate($request, $report);
        }

        return $this->handleReportUpdate($request, $report);
    }

    // === จัดการ Report ทั่วไป ===
    private function handleReportUpdate(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,reviewing,resolved,dismissed'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'admin_reply' => ['nullable', 'string', 'max:2000'],
        ]);

        $updateData = [];

        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];

            // บันทึก timeline timestamps
            if ($validated['status'] === 'reviewing' && !$report->reviewing_at) {
                $updateData['reviewing_at'] = now();
            }
            if (in_array($validated['status'], ['resolved', 'dismissed']) && !$report->resolved_at) {
                $updateData['resolved_at'] = now();
            }
        }

        if (isset($validated['admin_note'])) {
            $updateData['admin_note'] = $validated['admin_note'];
        }

        // Admin reply
        if (isset($validated['admin_reply'])) {
            $updateData['admin_reply'] = $validated['admin_reply'];
            $updateData['admin_reply_at'] = now();
            $updateData['admin_reply_by'] = $request->user()->id;
        }

        $report->update($updateData);

        // แจ้งเตือนคนที่ report
        if (isset($validated['status'])) {
            Notification::create([
                'user_id' => $report->reporter_id,
                'type' => 'system',
                'title' => 'Report updated',
                'message' => "Your report ({$report->report_code}) has been updated to: {$validated['status']}.",
            ]);
        }

        $report->load(['reporter:id,name,email', 'reportedUser:id,name,email', 'repliedBy:id,name']);

        return response()->json([
            'message' => 'Report updated successfully.',
            'report' => $report,
        ]);
    }

    // === จัดการ Dispute (พร้อม escrow) ===
    private function handleDisputeUpdate(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:open,resolved_buyer,resolved_seller'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'admin_reply' => ['nullable', 'string', 'max:2000'],
        ]);

        // ถ้าเป็นการ resolve dispute (มี escrow)
        if (isset($validated['status']) && in_array($validated['status'], ['resolved_buyer', 'resolved_seller'])) {
            if ($report->status !== 'open') {
                return response()->json([
                    'message' => 'This dispute has already been resolved.',
                ], 422);
            }

            $report->load(['order.product', 'order.user.wallet', 'order.seller.wallet']);
            $order = $report->order;

            DB::transaction(function () use ($report, $order, $validated, $request) {
                $updateData = [
                    'status' => $validated['status'],
                    'admin_note' => $validated['admin_note'] ?? $report->admin_note,
                    'resolved_at' => now(),
                ];

                if (isset($validated['admin_reply'])) {
                    $updateData['admin_reply'] = $validated['admin_reply'];
                    $updateData['admin_reply_at'] = now();
                    $updateData['admin_reply_by'] = $request->user()->id;
                }

                $report->update($updateData);

                if ($validated['status'] === 'resolved_buyer') {
                    $this->refundEscrow($order);
                    $order->status = 'cancelled';
                    $order->save();
                } else {
                    $this->releaseEscrow($order);
                    $order->status = 'completed';
                    $order->save();
                }

                // แจ้งเตือนทั้ง 2 ฝั่ง
                $resultText = $validated['status'] === 'resolved_buyer'
                    ? 'resolved in favor of the buyer. Refund has been processed.'
                    : 'resolved in favor of the seller. Payment has been released.';

                Notification::create([
                    'user_id' => $order->user_id,
                    'type' => 'order',
                    'title' => 'Dispute resolved',
                    'message' => "The dispute for {$order->product->name} has been {$resultText}",
                    'product_id' => $order->product_id,
                ]);

                Notification::create([
                    'user_id' => $order->seller_id,
                    'type' => 'order',
                    'title' => 'Dispute resolved',
                    'message' => "The dispute for {$order->product->name} has been {$resultText}",
                    'product_id' => $order->product_id,
                ]);
            });
        } else {
            // อัปเดตอื่นๆ (admin_note, admin_reply) ที่ไม่ใช่ resolve
            $updateData = [];
            if (isset($validated['admin_note'])) {
                $updateData['admin_note'] = $validated['admin_note'];
            }
            if (isset($validated['admin_reply'])) {
                $updateData['admin_reply'] = $validated['admin_reply'];
                $updateData['admin_reply_at'] = now();
                $updateData['admin_reply_by'] = $request->user()->id;
            }
            if (!empty($updateData)) {
                $report->update($updateData);
            }
        }

        $report->load([
            'reporter:id,name,email',
            'reportedUser:id,name,email',
            'order.product:id,name',
            'repliedBy:id,name',
        ]);

        return response()->json([
            'message' => 'Dispute updated successfully.',
            'report' => $report,
        ]);
    }

    // GET /api/admin/users - ดู users ทั้งหมด
    public function users(Request $request)
    {
        $query = User::with('wallet:id,user_id,balance_available');

        // ค้นหาตามชื่อหรือ email (?search=)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        // เพิ่ม ban status ให้ทุก user
        $users->getCollection()->transform(function ($user) {
            $user->is_banned = $user->is_banned;
            $user->banned_until = $user->active_banned_until;
            $user->ban_reason = $user->ban_reason;
            return $user;
        });

        return response()->json($users);
    }

    // GET /api/admin/users/{id} - ดูรายละเอียด user
    public function userDetail(Request $request, $id)
    {
        $user = User::with(['wallet', 'strikes'])
            ->withCount(['products', 'orders', 'reports'])
            ->findOrFail($id);

        // นับจำนวน report ที่ถูกรายงาน
        $user->reported_count = Report::where('reported_user_id', $id)->count();

        // เพิ่ม ban status
        $user->is_banned = $user->is_banned;
        $user->banned_until = $user->active_banned_until;
        $user->ban_reason = $user->ban_reason;

        return response()->json($user);
    }

    // POST /api/admin/users/{id}/ban - แบน user
    public function banUser(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'ban_days' => ['required', 'integer', 'min:1'],
        ]);

        $user = User::findOrFail($id);

        // ห้ามแบน admin
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Cannot ban an admin user.',
            ], 422);
        }

        $strike = UserStrike::create([
            'user_id' => $user->id,
            'reason' => $validated['reason'],
            'banned_until' => now()->addDays($validated['ban_days']),
        ]);

        // แจ้งเตือน user ที่ถูกแบน
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Account suspended',
            'message' => "Your account has been suspended for {$validated['ban_days']} days. Reason: {$validated['reason']}",
        ]);

        return response()->json([
            'message' => "User {$user->name} has been banned for {$validated['ban_days']} days.",
            'strike' => $strike,
        ]);
    }

    // POST /api/admin/users/{id}/unban - ปลดแบน user
    public function unbanUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // หา strike ที่ยังไม่หมดอายุ
        $activeStrikes = UserStrike::where('user_id', $user->id)
            ->where('banned_until', '>', now())
            ->get();

        if ($activeStrikes->isEmpty()) {
            return response()->json([
                'message' => 'This user is not currently banned.',
            ], 422);
        }

        // set banned_until = now() (ปลดแบนทันที)
        UserStrike::where('user_id', $user->id)
            ->where('banned_until', '>', now())
            ->update(['banned_until' => now()]);

        // แจ้งเตือน user
        Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Account unbanned',
            'message' => 'บัญชีของคุณถูกปลดแบนแล้ว สามารถใช้งานได้ตามปกติ',
        ]);

        return response()->json([
            'message' => "User {$user->name} has been unbanned.",
        ]);
    }

    // === Helper: คืนเงิน escrow ให้ buyer ===
    private function refundEscrow(Order $order): void
    {
        $buyerWallet = $order->user->wallet;
        if ($buyerWallet) {
            $buyerWallet->balance_pending -= $order->final_price;
            $buyerWallet->balance_available += $order->final_price;
            $buyerWallet->save();

            WalletTransaction::create([
                'user_id' => $order->user_id,
                'wallet_id' => $buyerWallet->id,
                'type' => 'escrow_refund',
                'amount' => $order->final_price,
                'description' => "Refund: {$order->product->name} (dispute resolved)",
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'balance_after' => $buyerWallet->balance_available,
            ]);
        }
    }

    // === Helper: โอนเงิน escrow ให้ seller ===
    private function releaseEscrow(Order $order): void
    {
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
                'description' => "Payment released: {$order->product->name} (dispute resolved)",
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'balance_after' => $buyerWallet->balance_available,
            ]);
        }

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
                'description' => "Sold: {$order->product->name} (dispute resolved)",
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'balance_after' => $sellerWallet->balance_available,
            ]);
        }
    }

    // === Certificate Management ===

    // GET /api/admin/certificates - ดู certificates ทั้งหมด
    public function certificates(Request $request)
    {
        $query = ProductCertificate::with([
            'product:id,name,user_id',
            'product.user:id,name,email',
            'verifier:id,name',
        ]);

        // กรองตาม status (?status=pending)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $certificates = $query->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($certificates);
    }

    // GET /api/admin/certificates/{id} - ดู/ดาวน์โหลดไฟล์ certificate
    public function viewCertificate($id)
    {
        $cert = ProductCertificate::findOrFail($id);

        if (!Storage::disk('public')->exists($cert->file_path)) {
            return response()->json(['message' => 'Certificate file not found'], 404);
        }

        return response()->json([
            'certificate' => $cert->load(['product:id,name,user_id', 'product.user:id,name']),
            'download_url' => asset('storage/' . $cert->file_path),
        ]);
    }

    // PATCH /api/admin/certificates/{id}/verify - อนุมัติ/ปฏิเสธ certificate
    public function verifyCertificate(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $cert = ProductCertificate::with('product')->findOrFail($id);

        $cert->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        // แจ้งเตือนเจ้าของสินค้า
        $statusText = $validated['status'] === 'approved'
            ? 'ผ่านการตรวจสอบแล้ว! สินค้าของคุณได้รับแท็ก Certified แล้ว'
            : 'ไม่ผ่านการตรวจสอบ';

        Notification::create([
            'user_id' => $cert->product->user_id,
            'type' => 'system',
            'title' => 'Certificate ' . $validated['status'],
            'message' => "ใบเซอร์สำหรับ {$cert->product->name}: {$statusText}",
            'product_id' => $cert->product_id,
        ]);

        return response()->json([
            'message' => 'Certificate ' . $validated['status'] . ' successfully.',
            'certificate' => $cert->fresh(['product:id,name', 'verifier:id,name']),
        ]);
    }

    // === Product Approval ===

    // GET /api/admin/products - ดูสินค้าทั้งหมด (กรองตาม status ได้)
    public function pendingProducts(Request $request)
    {
        $query = Product::with([
            'user:id,name,email',
            'category:id,name',
            'subcategory:id,name',
            'images',
            'certificate',
        ])->withCount('bids');

        // กรองตาม status (?status=pending) — default: pending
        $status = $request->input('status', 'pending');
        $query->where('status', $status);

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($products);
    }

    // PATCH /api/admin/products/{id}/approve - อนุมัติสินค้า
    public function approveProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($product->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending products can be approved. Current status: ' . $product->status,
            ], 422);
        }

        $product->update([
            'status' => 'active',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        // แจ้งเตือนผู้ขาย
        Notification::create([
            'user_id' => $product->user_id,
            'type' => 'system',
            'title' => 'สินค้าได้รับการอนุมัติ',
            'message' => "สินค้า \"{$product->name}\" ของคุณได้รับการอนุมัติแล้ว พร้อมเปิดประมูล!",
            'product_id' => $product->id,
        ]);

        // แจ้ง watchers ถ้าสินค้าเริ่มประมูลทันที
        if (!$product->auction_start_time || $product->auction_start_time->isPast()) {
            $watcherIds = \App\Models\ProductWatch::where('product_id', $product->id)
                ->pluck('user_id');

            foreach ($watcherIds as $watcherId) {
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

        return response()->json([
            'message' => 'Product approved successfully.',
            'product' => $product->fresh(['user:id,name', 'images']),
        ]);
    }

    // PATCH /api/admin/products/{id}/reject - ปฏิเสธสินค้า
    public function rejectProduct(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $product = Product::findOrFail($id);

        if ($product->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending products can be rejected. Current status: ' . $product->status,
            ], 422);
        }

        $product->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        // แจ้งเตือนผู้ขาย
        Notification::create([
            'user_id' => $product->user_id,
            'type' => 'system',
            'title' => 'สินค้าถูกปฏิเสธ',
            'message' => "สินค้า \"{$product->name}\" ของคุณถูกปฏิเสธ เหตุผล: {$validated['admin_note']}",
            'product_id' => $product->id,
        ]);

        return response()->json([
            'message' => 'Product rejected.',
            'product' => $product->fresh(['user:id,name', 'images']),
        ]);
    }

    // === Withdrawal Management ===

    // GET /api/admin/withdrawals - ดูรายการถอนเงินทั้งหมด
    public function withdrawals(Request $request)
    {
        $query = WalletTransaction::where('type', 'withdraw')
            ->with(['user:id,name,email,phone_number']);

        // กรองตาม status (?status=pending)
        if ($request->has('status')) {
            $query->where('withdraw_status', $request->status);
        }

        $withdrawals = $query->orderByRaw("FIELD(withdraw_status, 'pending', 'confirmed', 'rejected')")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($withdrawals);
    }

    // PATCH /api/admin/withdrawals/{id}/confirm - ยืนยันการถอนเงิน (โอนเงินให้แล้ว)
    public function confirmWithdrawal(Request $request, $id)
    {
        $transaction = WalletTransaction::where('type', 'withdraw')->findOrFail($id);

        if ($transaction->withdraw_status !== 'pending') {
            return response()->json([
                'message' => 'This withdrawal has already been processed. Status: ' . $transaction->withdraw_status,
            ], 422);
        }

        $transaction->update([
            'withdraw_status' => 'confirmed',
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        // แจ้งเตือน user
        Notification::create([
            'user_id' => $transaction->user_id,
            'type' => 'system',
            'title' => 'ถอนเงินสำเร็จ',
            'message' => 'การถอนเงิน ' . number_format(abs($transaction->amount)) . ' บาท ได้รับการยืนยันแล้ว เงินจะโอนเข้าบัญชีของคุณ',
        ]);

        return response()->json([
            'message' => 'Withdrawal confirmed.',
            'transaction' => $transaction->fresh(['user:id,name']),
        ]);
    }

    // PATCH /api/admin/withdrawals/{id}/reject - ปฏิเสธการถอนเงิน (คืนเงินกลับ wallet)
    public function rejectWithdrawal(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $transaction = WalletTransaction::where('type', 'withdraw')->findOrFail($id);

        if ($transaction->withdraw_status !== 'pending') {
            return response()->json([
                'message' => 'This withdrawal has already been processed. Status: ' . $transaction->withdraw_status,
            ], 422);
        }

        // คืนเงินกลับ wallet
        $wallet = $transaction->user->wallet;
        if ($wallet) {
            $wallet->balance_available += abs($transaction->amount);
            $wallet->balance_total += abs($transaction->amount);
            $wallet->withdraw -= abs($transaction->amount);
            $wallet->save();
        }

        $transaction->update([
            'withdraw_status' => 'rejected',
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => now(),
            'description' => $transaction->description . " [REJECTED: {$validated['admin_note']}]",
        ]);

        // แจ้งเตือน user
        Notification::create([
            'user_id' => $transaction->user_id,
            'type' => 'system',
            'title' => 'การถอนเงินถูกปฏิเสธ',
            'message' => 'การถอนเงิน ' . number_format(abs($transaction->amount)) . " บาท ถูกปฏิเสธ เหตุผล: {$validated['admin_note']} เงินถูกคืนกลับเข้า wallet แล้ว",
        ]);

        return response()->json([
            'message' => 'Withdrawal rejected. Funds returned to user wallet.',
            'transaction' => $transaction->fresh(['user:id,name']),
        ]);
    }
}
