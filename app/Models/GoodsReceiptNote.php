<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\Account\Models\Vendor;

class GoodsReceiptNote extends Model
{
    protected $fillable = [
        'grn_number',
        'receipt_date',
        'purchase_order_id',
        'vendor_id',
        'warehouse_id',
        'total_quantity',
        'status',
        'inspection_notes',
        'received_by',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_quantity' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class, 'goods_receipt_note_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($grn) {
            if (empty($grn->grn_number)) {
                $grn->grn_number = static::generateGrnNumber();
            }
        });
    }

    public static function generateGrnNumber(): string
    {
        $date = date('Ymd');
        $lastGrn = static::where('grn_number', 'like', "GRN-{$date}-%")
            ->where('created_by', creatorId())
            ->orderBy('grn_number', 'desc')
            ->first();

        if ($lastGrn) {
            $lastNumber = (int) substr($lastGrn->grn_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "GRN-{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
