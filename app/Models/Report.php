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
        'order_id',
        'type',
        'description',
        'evidence_images',
        'status',
        'admin_note',
        'resolved_at',
        'reviewing_at',
        'admin_reply',
        'admin_reply_at',
        'admin_reply_by',
    ];

    protected $casts = [
        'evidence_images' => 'array',
        'resolved_at' => 'datetime',
        'reviewing_at' => 'datetime',
        'admin_reply_at' => 'datetime',
    ];

    protected $appends = ['report_code', 'timeline'];

    // === Accessors ===

    public function getReportCodeAttribute(): string
    {
        return 'RPT-' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    public function getTimelineAttribute(): array
    {
        $timeline = [];

        $timeline[] = [
            'status' => 'submitted',
            'label' => 'ส่งรายงานแล้ว',
            'date' => $this->created_at?->toISOString(),
        ];

        if ($this->reviewing_at) {
            $timeline[] = [
                'status' => 'reviewing',
                'label' => 'กำลังดำเนินการ',
                'date' => $this->reviewing_at->toISOString(),
            ];
        }

        if ($this->resolved_at) {
            $label = match ($this->status) {
                'resolved_buyer' => 'ตัดสินให้ผู้ซื้อ',
                'resolved_seller' => 'ตัดสินให้ผู้ขาย',
                'dismissed' => 'ยกเลิกรายงาน',
                default => 'แก้ไขเสร็จสิ้น',
            };
            $timeline[] = [
                'status' => 'resolved',
                'label' => $label,
                'date' => $this->resolved_at->toISOString(),
            ];
        }

        return $timeline;
    }

    // === Helpers ===

    public function isDispute(): bool
    {
        return $this->type === 'dispute';
    }

    // === Relationships ===

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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'admin_reply_by');
    }
}
