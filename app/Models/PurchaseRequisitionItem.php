<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\ProductService\Models\ProductServiceItem;

class PurchaseRequisitionItem extends Model
{
    protected $fillable = [
        'purchase_requisition_id',
        'product_service_id',
        'quantity',
        'estimated_unit_price',
        'purpose',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductServiceItem::class, 'product_service_id');
    }
}
