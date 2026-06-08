<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\Account\Models\Vendor;

class RfqVendor extends Model
{
    protected $fillable = [
        'request_quotation_id',
        'vendor_id',
        'total_price',
        'response',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'responded_at' => 'datetime',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(RequestQuotation::class, 'request_quotation_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
