<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\ProductService\Models\ProductServiceItem;

class GrnItem extends Model
{
    protected $fillable = [
        'goods_receipt_note_id',
        'purchase_order_item_id',
        'product_service_id',
        'quantity_ordered',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'unit_price',
        'total_amount',
        'inspection_result',
        'rejection_reason',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'quantity_accepted' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'goods_receipt_note_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductServiceItem::class, 'product_service_id');
    }
}
