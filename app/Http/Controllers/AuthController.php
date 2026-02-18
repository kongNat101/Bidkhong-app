<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

        // Load wallet relationship
        $user->load('wallet');

        return response()->json([
            'user' => $user,
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

        // Load wallet relationship
        $user->load('wallet');

        return response()->json([
            'user' => $user,
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
        // Load wallet relationship to include wallet data
        $user = $request->user()->load('wallet');
        return response()->json($user);
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
        $user->load('wallet');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
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

    // POST /api/wallet/topup - เติมเงิน
    public function topup(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        $result = DB::transaction(function () use ($wallet, $validated, $user) {
            $wallet = $wallet->lockForUpdate()->find($wallet->id);

            $wallet->balance_available += $validated['amount'];
            $wallet->balance_total += $validated['amount'];
            $wallet->deposit += $validated['amount'];
            $wallet->save();

            // Create transaction record
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'topup',
                'amount' => $validated['amount'],
                'description' => 'Top Up - Mobile Banking',
                'balance_after' => $wallet->balance_available,
            ]);

            return $wallet;
        });

        return response()->json([
            'message' => 'Topup successful',
            'balance_available' => $result->balance_available,
        ]);
    }

    // GET /api/wallet/transactions - Get wallet transactions
    public function getTransactions(Request $request)
    {
        $user = $request->user();
        $transactions = WalletTransaction::where('user_id', $user->id)
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

            // Create transaction record
            WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'withdraw',
                'amount' => -$validated['amount'],
                'description' => "Withdraw to {$validated['bank_code']} - {$validated['account_number']}",
                'balance_after' => $wallet->balance_available,
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
            'balance_available' => $result->balance_available,
            'bank_code' => $validated['bank_code'],
            'account_number' => $validated['account_number'],
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

    // POST /api/forgot-password - ขอ reset password
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // ลบ token เก่า (ถ้ามี)
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // สร้าง token ใหม่
        $token = \Illuminate\Support\Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // ในระบบจริงจะส่ง email ให้ user — ตอนนี้คืน token กลับมาให้ frontend
        return response()->json([
            'message' => 'Password reset token generated',
            'token' => $token,
            'note' => 'In production, this token would be sent via email',
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