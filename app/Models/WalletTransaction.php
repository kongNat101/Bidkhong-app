<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_id',
        'type',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'balance_after',
        'slip_image',
        'slip_status',
        'slip_data',
        'verified_at',
        'withdraw_status',
        'confirmed_by',
        'confirmed_at',
        'slip_ref',
        'bank_code',
        'account_number',
        'account_name',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'slip_data' => 'array',
        'verified_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
