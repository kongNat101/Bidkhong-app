<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'reporter_id',
        'reason',
        'evidence_images',
        'status',
        'admin_note',
        'resolved_at',
    ];

    protected $casts = [
        'evidence_images' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class , 'reporter_id');
    }
}