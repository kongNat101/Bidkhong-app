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
        'min_price',
        'buyout_price',
        'auction_end_time',
        'location',
        'picture',
        'status',
    ];

    protected $casts = [
        'auction_end_time' => 'datetime',
        'starting_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'buyout_price' => 'decimal:2',
    ];

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

    // คำนวณ min bid increment จาก buyout_price (ลดลง 1 หลัก)
    public function getBidIncrement(): int
    {
        $price = (int) $this->buyout_price;
        $digits = strlen((string) $price);
        $increment = (int) pow(10, max($digits - 1, 0));

        return max($increment, 1);
    }
}