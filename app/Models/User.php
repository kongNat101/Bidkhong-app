<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'role',
        'profile_image',
        'rating',
        'total_reviews',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['is_banned', 'active_banned_until', 'ban_reason'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rating' => 'decimal:2',
        ];
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class , 'reporter_id');
    }

    public function strikes()
    {
        return $this->hasMany(UserStrike::class);
    }

    // รีวิวที่ได้รับในฐานะผู้ขาย
    public function receivedReviews()
    {
        return $this->hasMany(Review::class , 'seller_id');
    }

    // rating + total_reviews เก็บใน DB ตรงๆ แล้ว (อัปเดตเมื่อมีรีวิวใหม่)

    // === Ban Status Accessors ===

    // เช็คว่าถูกแบนอยู่หรือไม่
    public function getIsBannedAttribute(): bool
    {
        return $this->strikes()
            ->where('banned_until', '>', now())
            ->exists();
    }

    // วันหมดแบน (strike ล่าสุดที่ยังมีผล)
    public function getActiveBannedUntilAttribute(): ?string
    {
        $strike = $this->strikes()
            ->where('banned_until', '>', now())
            ->orderByDesc('banned_until')
            ->first();
        return $strike?->banned_until?->toISOString();
    }

    // เหตุผลที่ถูกแบน (strike ล่าสุดที่ยังมีผล)
    public function getBanReasonAttribute(): ?string
    {
        $strike = $this->strikes()
            ->where('banned_until', '>', now())
            ->orderByDesc('banned_until')
            ->first();
        return $strike?->reason;
    }
}