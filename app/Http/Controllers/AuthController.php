<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Mail\PasswordResetMail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:users'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Laravel casts: 'hashed' จัดการให้อัตโนมัติ
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        // สร้าง wallet ให้ user อัตโนมัติ
        $user->wallet()->create([
            'balance_available' => 0,
            'balance_total' => 0,
            'balance_pending' => 0,
            'withdraw' => 0,
            'deposit' => 0,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $userData = $user->toArray();
        $userData['wallet'] = self::getWalletData($user->id);

        return response()->json([
            'user' => $userData,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $userData = $user->toArray();
        $userData['wallet'] = self::getWalletData($user->id);

        return response()->json([
            'user' => $userData,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $userData = $user->toArray();
        $userData['wallet'] = self::getWalletData($user->id);
        return response()->json($userData);
    }

    // PATCH /api/profile - แก้ไขข้อมูลส่วนตัว
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone_number' => ['sometimes', 'string', 'max:20', 'unique:users,phone_number,' . $user->id],
        ]);

        // เช็คว่ามีข้อมูลส่งมาไหม
        if (empty($validated)) {
            return response()->json([
                'message' => 'No data provided for update',
            ], 422);
        }

        // เช็คว่าค่าที่ส่งมาต่างจากค่าปัจจุบันหรือไม่
        $changes = array_filter($validated, function ($value, $key) use ($user) {
            return $user->{$key} !== $value;
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($changes)) {
            return response()->json([
                'message' => 'No changes detected',
            ], 422);
        }

        $user->update($changes);

        $userData = $user->fresh()->toArray();
        $userData['wallet'] = self::getWalletData($user->id);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $userData,
        ]);
    }

    // POST /api/change-password - เปลี่ยนรหัสผ่าน (ต้อง login อยู่)
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->update([
            'password' => $validated['new_password'],
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    // POST /api/wallet/topup - เติมเงิน (ต้องแนบสลิป + verify ผ่าน Slip2Go API)
    public function topup(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'slip_image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        // เรียก Slip2Go API ก่อน store (เพื่อให้ getRealPath() ยังใช้ได้)
        $slipFile = $request->file('slip_image');
        $slipVerification = $this->verifySlipWithSlip2Go($slipFile);

        // อัปโหลดสลิป (หลัง verify แล้ว)
        $slipPath = $slipFile->store('slips', 'public');

        if (!$slipVerification['success']) {
            // สลิป verify ไม่ผ่าน → บันทึก transaction แต่ไม่เพิ่มเงิน
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'topup',
                'amount' => $validated['amount'],
                'description' => 'Top-up failed — slip verification rejected',
                'balance_after' => $wallet->balance_available,
                'slip_image' => $slipPath,
                'slip_status' => 'rejected',
                'slip_data' => $slipVerification['data'],
            ]);

            return response()->json([
                'message' => 'Slip verification failed: ' . ($slipVerification['reason'] ?? 'Invalid slip'),
                'slip_status' => 'rejected',
            ], 400);
        }

        $slipData = $slipVerification['data'];
        $slipAmount = $slipData['amount'] ?? null;
        $slipRef = $slipData['transRef'] ?? null;

        // ตรวจสอบยอดเงินในสลิปตรงกับที่ส่งมาหรือไม่ (ใช้ bccomp ป้องกัน float error)
        if ($slipAmount !== null && bccomp((string) $slipAmount, (string) $validated['amount'], 2) !== 0) {
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'topup',
                'amount' => $validated['amount'],
                'description' => "Top-up failed — amount mismatch (slip: {$slipAmount}, requested: {$validated['amount']})",
                'balance_after' => $wallet->balance_available,
                'slip_image' => $slipPath,
                'slip_status' => 'rejected',
                'slip_data' => $slipData,
                'slip_ref' => $slipRef,
            ]);

            return response()->json([
                'message' => "Amount mismatch: slip amount is {$slipAmount} but requested {$validated['amount']}",
                'slip_status' => 'rejected',
            ], 400);
        }

        // ตรวจสอบสลิปซ้ำ (duplicate slip)
        if ($slipRef && WalletTransaction::where('slip_ref', $slipRef)->exists()) {
            return response()->json([
                'message' => 'This slip has already been used',
                'slip_status' => 'rejected',
            ], 400);
        }

        // สลิป verify สำเร็จ → เพิ่มเงินใน DB transaction เพื่อป้องกัน race condition
        try {
            $result = DB::transaction(function () use ($wallet, $validated, $user, $slipPath, $slipData, $slipRef) {
                $wallet = Wallet::lockForUpdate()->find($wallet->id);

                // Double-check สลิปซ้ำภายใน transaction (ป้องกัน race condition)
                if ($slipRef && WalletTransaction::where('slip_ref', $slipRef)->exists()) {
                    throw new \Exception('Duplicate slip detected');
                }

                $wallet->balance_available += $validated['amount'];
                $wallet->balance_total += $validated['amount'];
                $wallet->deposit += $validated['amount'];
                $wallet->save();

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'type' => 'topup',
                    'amount' => $validated['amount'],
                    'description' => 'Wallet top-up via bank transfer',
                    'balance_after' => $wallet->balance_available,
                    'slip_image' => $slipPath,
                    'slip_status' => 'verified',
                    'slip_data' => $slipData,
                    'slip_ref' => $slipRef,
                    'verified_at' => now(),
                ]);

                return $wallet;
            });
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'slip_ref')) {
                return response()->json([
                    'message' => 'This slip has already been used',
                    'slip_status' => 'rejected',
                ], 400);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Topup successful',
            'slip_status' => 'verified',
            'wallet' => self::getWalletData($user->id),
        ]);
    }

    // เรียก Slip2Go API เพื่อตรวจสอบสลิป
    private function verifySlipWithSlip2Go($slipFile): array
    {
        $apiKey = config('services.slip2go.api_key');
        $baseUrl = rtrim(config('services.slip2go.base_url'), '/');

        if (!$apiKey) {
            // ถ้ายังไม่ได้ตั้งค่า API key → dev mode เฉพาะ local เท่านั้น
            if (app()->environment('local')) {
                return [
                    'success' => true,
                    'data' => ['message' => 'Slip2Go API key not configured - auto approved (dev mode)'],
                    'reason' => null,
                ];
            }
            // Production/staging ต้องมี API key เสมอ
            return [
                'success' => false,
                'data' => ['error' => 'Slip verification service not configured'],
                'reason' => 'Slip verification service not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->attach(
                'file', file_get_contents($slipFile->getRealPath()), $slipFile->getClientOriginalName()
            )->post("{$baseUrl}/api/verify-slip/qr-image/info");

            $data = $response->json();

            if ($response->successful() && isset($data['data'])) {
                return [
                    'success' => true,
                    'data' => $data['data'],
                    'reason' => null,
                ];
            }

            return [
                'success' => false,
                'data' => $data,
                'reason' => $data['message'] ?? 'Slip verification failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => ['error' => $e->getMessage()],
                'reason' => 'Slip verification service unavailable',
            ];
        }
    }

    // Helper: คำนวณ wallet data ที่ถูกต้อง (ใช้ทุก endpoint)
    public static function getWalletData(int $userId): ?array
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        if (!$wallet) return null;

        // นับทั้ง active (กำลังประมูล) + won (ชนะแล้วรอ confirm/ship/receive)
        $activeBidsPending = \App\Models\Bid::where('user_id', $userId)
            ->whereIn('status', ['active', 'won'])
            ->sum('price');

        $balanceAvailable = max(0, $wallet->balance_total - $activeBidsPending);

        return [
            'balance_available' => number_format($balanceAvailable, 2, '.', ''),
            'balance_total' => $wallet->balance_total,
            'balance_pending' => number_format($activeBidsPending, 2, '.', ''),
            'withdraw' => $wallet->withdraw,
            'deposit' => $wallet->deposit,
        ];
    }

    // GET /api/wallet - ดูยอดเงินใน wallet (realtime)
    public function getWallet(Request $request)
    {
        $walletData = self::getWalletData($request->user()->id);

        if (!$walletData) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        return response()->json($walletData);
    }

    // GET /api/wallet/transactions - Get wallet transactions (topup + withdraw เท่านั้น)
    public function getTransactions(Request $request)
    {
        $user = $request->user();
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->whereNotIn('type', ['bid_placed', 'bid_refund'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($transactions);
    }

    // POST /api/wallet/withdraw - ถอนเงิน
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:100'], // ขั้นต่ำ 100 บาท
            'bank_code' => ['required', 'string'],
            'account_number' => ['required', 'string', 'min:10', 'max:15'],
            'account_name' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        $result = DB::transaction(function () use ($wallet, $validated, $user) {
            $wallet = $wallet->lockForUpdate()->find($wallet->id);

            // ตรวจสอบว่ามีเงินพอถอนไหม (ตรวจอีกครั้งหลัง lock)
            if ($wallet->balance_available < $validated['amount']) {
                return null;
            }

            // หักเงินจาก wallet
            $wallet->balance_available -= $validated['amount'];
            $wallet->balance_total -= $validated['amount'];
            $wallet->withdraw += $validated['amount'];
            $wallet->save();

            // Create transaction record (status = pending → รอ admin confirm)
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'withdraw',
                'amount' => -$validated['amount'],
                'description' => "Withdrawal request submitted to {$validated['bank_code']} ({$validated['account_name']})",
                'balance_after' => $wallet->balance_available,
                'withdraw_status' => 'pending',
                'bank_code' => $validated['bank_code'],
                'account_number' => $validated['account_number'],
                'account_name' => $validated['account_name'],
            ]);

            return $wallet;
        });

        if (!$result) {
            return response()->json([
                'message' => 'Insufficient balance',
                'balance_available' => $wallet->fresh()->balance_available,
            ], 400);
        }

        return response()->json([
            'message' => 'Withdrawal request submitted successfully',
            'amount' => $validated['amount'],
            'bank_code' => $validated['bank_code'],
            'account_number' => $validated['account_number'],
            'wallet' => self::getWalletData($user->id),
        ]);
    }

    // POST /api/profile/image - อัปโหลดรูปโปรไฟล์
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], // max 2MB
        ]);

        $user = $request->user();

        // ลบรูปเก่า (ถ้ามี)
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        // เก็บรูปใหม่ (เก็บแค่ relative path)
        $path = $request->file('profile_image')->store('profiles', 'public');

        $user->update(['profile_image' => $path]);

        return response()->json([
            'message' => 'Profile image updated successfully',
            'profile_image' => $path,
        ]);
    }

    // POST /api/forgot-password - ขอ reset password (ส่ง OTP 6 หลักไปเมลจริง)
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // ลบ token เก่า (ถ้ามี)
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // สร้าง OTP 6 หลัก
        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // ส่งเมลจริง
        Mail::to($request->email)->send(new PasswordResetMail(
            token: $token,
            email: $request->email,
        ));

        return response()->json([
            'message' => 'Password reset code has been sent to your email.',
        ]);
    }

    // POST /api/reset-password - เปลี่ยน password ด้วย token
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // หา token ในฐานข้อมูล
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'No reset token found for this email'
            ], 400);
        }

        // เช็คว่า token ตรงกัน
        if (!Hash::check($request->token, $record->token)) {
            return response()->json([
                'message' => 'Invalid reset token'
            ], 400);
        }

        // เช็คว่า token ไม่เกิน 60 นาที
        if (now()->diffInMinutes($record->created_at) > 60) {
            // ลบ token ที่หมดอายุ
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'message' => 'Reset token has expired (valid for 60 minutes)'
            ], 400);
        }

        // เปลี่ยน password
        $user = User::where('email', $request->email)->first();
        $user->update(['password' => $request->password]); // Laravel casts: 'hashed' จัดการให้

        // ลบ token ที่ใช้แล้ว
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // ลบ tokens ทั้งหมดของ user (force logout)
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully. Please login with your new password.',
        ]);
    }
}