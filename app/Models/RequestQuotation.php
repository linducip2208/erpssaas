<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestQuotation extends Model
{
    protected $fillable = [
        'rfq_number',
        'rfq_date',
        'due_date',
        'purchase_requisition_id',
        'status',
        'terms_conditions',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'rfq_date' => 'date',
        'due_date' => 'date',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class, 'request_quotation_id');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(RfqVendor::class, 'request_quotation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'request_quotation_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rfq) {
            if (empty($rfq->rfq_number)) {
                $rfq->rfq_number = static::generateRfqNumber();
            }
        });
    }

    public static function generateRfqNumber(): string
    {
        $date = date('Ymd');
        $lastRfq = static::where('rfq_number', 'like', "RFQ-{$date}-%")
            ->where('created_by', creatorId())
            ->orderBy('rfq_number', 'desc')
            ->first();

        if ($lastRfq) {
            $lastNumber = (int) substr($lastRfq->rfq_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "RFQ-{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
