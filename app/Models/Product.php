<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'subcategory_id',
        'name',
        'description',
        'starting_price',
        'current_price',
        'bid_increment',
        'buyout_price',
        'auction_start_time',
        'auction_end_time',
        'location',
        'picture',
        'status',
    ];

    protected $casts = [
        'auction_start_time' => 'datetime',
        'auction_end_time' => 'datetime',
        'starting_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'bid_increment' => 'decimal:2',
        'buyout_price' => 'decimal:2',
    ];

    protected $appends = ['tag', 'is_certified'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function certificate()
    {
        return $this->hasOne(ProductCertificate::class);
    }

    // ใบเซอร์ผ่านการตรวจสอบจาก admin หรือไม่
    public function getIsCertifiedAttribute(): bool
    {
        return $this->certificate && $this->certificate->status === 'approved';
    }

    // คำนวณ tag สถานะสินค้า (Priority: Hot > Ending > Ended > Incoming > Default)
    public function getTagAttribute(): string
    {
        // Hot: มี bids >= 10 ครั้ง
        $bidCount = $this->bids_count ?? $this->bids()->count();
        if ($bidCount >= 10) {
            return 'hot';
        }

        // Ended: หมดเวลาประมูลแล้ว
        if ($this->auction_end_time && $this->auction_end_time->isPast()) {
            return 'ended';
        }

        // Ending: เหลือเวลาประมูล <= 6 ชั่วโมง (และยังไม่หมดเวลา)
        if ($this->status === 'active' && $this->auction_end_time) {
            $minutesLeft = now()->diffInMinutes($this->auction_end_time, false);
            if ($minutesLeft > 0 && $minutesLeft <= 360) {
                return 'ending';
            }
        }

        // Incoming: สินค้าที่ลงแล้วแต่ยังไม่ถึงเวลาเริ่มประมูล (auction_start_time ยังไม่ถึง)
        if ($this->auction_start_time && $this->auction_start_time->isFuture()) {
            return 'incoming';
        }

        return 'default';
    }
}