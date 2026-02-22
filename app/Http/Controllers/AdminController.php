<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Report;
use App\Models\Dispute;
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
            'open_disputes' => Dispute::where('status', 'open')->count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'pending_certificates' => ProductCertificate::where('status', 'pending')->count(),
            'recent_orders' => Order::with(['product:id,name', 'user:id,name', 'seller:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get(),
        ]);
    }

    // GET /api/admin/reports - ดู reports ทั้งหมด
    public function reports(Request $request)
    {
        $query = Report::with([
            'reporter:id,name,email',
            'reportedUser:id,name,email',
            'reportedProduct:id,name',
        ]);

        // กรองตาม status (?status=pending)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->orderBy('created_at', 'desc')->get();

        return response()->json($reports);
    }

    // PATCH /api/admin/reports/{id} - อัปเดต report
    public function updateReport(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,reviewing,resolved,dismissed'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = Report::findOrFail($id);
        $report->update($validated);

        // แจ้งเตือนคนที่ report
        Notification::create([
            'user_id' => $report->reporter_id,
            'type' => 'system',
            'title' => 'Report updated',
            'message' => "Your report has been updated to: {$validated['status']}.",
        ]);

        $report->load(['reporter:id,name,email', 'reportedUser:id,name,email']);

        return response()->json([
            'message' => 'Report updated successfully.',
            'report' => $report,
        ]);
    }

    // GET /api/admin/disputes - ดู disputes ทั้งหมด
    public function disputes(Request $request)
    {
        $query = Dispute::with([
            'order.product:id,name',
            'order.user:id,name',
            'order.seller:id,name',
            'reporter:id,name,email',
        ]);

        // กรองตาม status (?status=open)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $disputes = $query->orderBy('created_at', 'desc')->get();

        return response()->json($disputes);
    }

    // PATCH /api/admin/disputes/{id}/resolve - ตัดสิน dispute
    public function resolveDispute(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:resolved_buyer,resolved_seller'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $dispute = Dispute::with(['order.product', 'order.user.wallet', 'order.seller.wallet'])->findOrFail($id);

        if ($dispute->status !== 'open') {
            return response()->json([
                'message' => 'This dispute has already been resolved.',
            ], 422);
        }

        $order = $dispute->order;

        DB::transaction(function () use ($dispute, $order, $validated) {
            // อัปเดต dispute
            $dispute->update([
                'status' => $validated['status'],
                'admin_note' => $validated['admin_note'] ?? null,
                'resolved_at' => now(),
            ]);

            if ($validated['status'] === 'resolved_buyer') {
                // คืนเงิน escrow ให้ buyer
                $this->refundEscrow($order);
                $order->status = 'cancelled';
                $order->save();
            }
            else {
                // จ่ายเงิน escrow ให้ seller
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

        return response()->json([
            'message' => 'Dispute resolved successfully.',
            'dispute' => $dispute->fresh(['order.product', 'reporter:id,name']),
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

    // === Helper: คืนเงิน escrow ให้ buyer ===
    private function refundEscrow(Order $order): void
    {
        $buyerWallet = $order->user->wallet;
        if ($buyerWallet) {
            // คืนจาก pending ไป available
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
                'description' => "Payment released: {$order->product->name} (dispute resolved)",
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
}