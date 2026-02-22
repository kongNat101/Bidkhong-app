<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seller_id',
        'product_id',
        'final_price',
        'order_date',
        'status',
        'buyer_confirmed_at',
        'shipped_at',
        'received_at',
        'confirm_deadline',
        'ship_deadline',
        'receive_deadline',
    ];

    protected $casts = [
        'final_price' => 'decimal:2',
        'order_date' => 'datetime',
        'buyer_confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'confirm_deadline' => 'datetime',
        'ship_deadline' => 'datetime',
        'receive_deadline' => 'datetime',
    ];

    // ผู้ชนะ (buyer)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ผู้ขาย (seller)
    public function seller()
    {
        return $this->belongsTo(User::class , 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function dispute()
    {
        return $this->hasOne(Report::class, 'order_id')->where('type', 'dispute');
    }

    // เช็คว่า confirm หมดเวลาหรือยัง
    public function isConfirmExpired(): bool
    {
        return $this->confirm_deadline && now()->gt($this->confirm_deadline);
    }
}