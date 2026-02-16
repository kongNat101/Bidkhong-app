<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance_available',
        'balance_total',
        'balance_pending',
        'withdraw',
        'deposit',
        'w_time',
    ];

    protected $casts =   [
        'balance_available' => 'decimal:2',
        'balance_total' => 'decimal:2',
        'balance_pending' => 'decimal:2',
        'withdraw' => 'decimal:2',
        'deposit' => 'decimal:2',
        'w_time' => 'datetime',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
