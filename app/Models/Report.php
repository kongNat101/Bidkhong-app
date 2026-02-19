<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'reported_product_id',
        'type',
        'description',
        'evidence_images',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'evidence_images' => 'array',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function reportedProduct()
    {
        return $this->belongsTo(Product::class, 'reported_product_id');
    }
}
