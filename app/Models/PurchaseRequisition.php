<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workdo\Hrm\Models\Department;

class PurchaseRequisition extends Model
{
    protected $fillable = [
        'pr_number',
        'requisition_date',
        'required_date',
        'requested_by',
        'department_id',
        'priority',
        'status',
        'reason',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'required_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'purchase_requisition_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'purchase_requisition_id');
    }

    public function requestQuotations(): HasMany
    {
        return $this->hasMany(RequestQuotation::class, 'purchase_requisition_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pr) {
            if (empty($pr->pr_number)) {
                $pr->pr_number = static::generatePrNumber();
            }
        });
    }

    public static function generatePrNumber(): string
    {
        $date = date('Ymd');
        $lastPr = static::where('pr_number', 'like', "PR-{$date}-%")
            ->where('created_by', creatorId())
            ->orderBy('pr_number', 'desc')
            ->first();

        if ($lastPr) {
            $lastNumber = (int) substr($lastPr->pr_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "PR-{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
