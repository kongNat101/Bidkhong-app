<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCertificate extends Model
{
    protected $fillable = [
        'product_id',
        'file_path',
        'original_name',
        'status',
        'admin_note',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class , 'verified_by');
    }
}