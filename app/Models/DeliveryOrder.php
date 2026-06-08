<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\Account\Models\Customer;

class DeliveryOrder extends Model
{
    protected $fillable = [
        'do_number',
        'delivery_date',
        'sales_order_id',
        'customer_id',
        'warehouse_id',
        'total_quantity',
        'status',
        'notes',
        'carrier',
        'tracking_number',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'total_quantity' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class, 'delivery_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($deliveryOrder) {
            if (empty($deliveryOrder->do_number)) {
                $deliveryOrder->do_number = static::generateDoNumber();
            }
        });
    }

    public static function generateDoNumber(): string
    {
        $date = date('Ymd');
        $lastDo = static::where('do_number', 'like', "DO-{$date}-%")
            ->where('created_by', creatorId())
            ->orderBy('do_number', 'desc')
            ->first();

        if ($lastDo) {
            $lastNumber = (int) substr($lastDo->do_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "DO-{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
