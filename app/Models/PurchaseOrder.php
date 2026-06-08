<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\Account\Models\Vendor;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'order_date',
        'expected_delivery_date',
        'vendor_id',
        'warehouse_id',
        'purchase_requisition_id',
        'request_quotation_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'status',
        'notes',
        'terms_conditions',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(RequestQuotation::class, 'request_quotation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function goodsReceiptNotes(): HasMany
    {
        return $this->hasMany(GoodsReceiptNote::class, 'purchase_order_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            if (empty($po->po_number)) {
                $po->po_number = static::generatePoNumber();
            }
        });
    }

    public static function generatePoNumber(): string
    {
        $date = date('Ymd');
        $lastPo = static::where('po_number', 'like', "PO-{$date}-%")
            ->where('created_by', creatorId())
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->po_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "PO-{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
