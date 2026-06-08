<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\ProductService\Models\ProductServiceItem;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_service_id',
        'quantity',
        'received_quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'discount_percentage',
        'discount_amount',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductServiceItem::class, 'product_service_id');
    }

    public function remainingQuantity(): float
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    public function calculateAmounts()
    {
        $lineTotal = $this->quantity * $this->unit_price;
        $this->discount_amount = ($lineTotal * $this->discount_percentage) / 100;
        $afterDiscount = $lineTotal - $this->discount_amount;
        $this->tax_amount = ($afterDiscount * $this->tax_rate) / 100;
        $this->total_amount = $afterDiscount + $this->tax_amount;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->calculateAmounts();
        });
    }
}
