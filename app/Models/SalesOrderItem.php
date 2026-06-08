<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\ProductService\Models\ProductServiceItem;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_service_id',
        'quantity',
        'delivered_quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'delivered_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductServiceItem::class, 'product_service_id');
    }

    public function remainingQuantity(): float
    {
        return max(0, $this->quantity - $this->delivered_quantity);
    }
}
