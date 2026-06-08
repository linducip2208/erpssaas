<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\ProductService\Models\ProductServiceItem;

class RfqItem extends Model
{
    protected $fillable = [
        'request_quotation_id',
        'product_service_id',
        'quantity',
        'purpose',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function requestQuotation(): BelongsTo
    {
        return $this->belongsTo(RequestQuotation::class, 'request_quotation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductServiceItem::class, 'product_service_id');
    }
}
