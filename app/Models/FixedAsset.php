<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Workdo\Account\Models\Vendor;

class FixedAsset extends Model
{
    protected $fillable = [
        'asset_code',
        'name',
        'description',
        'asset_category_id',
        'vendor_id',
        'purchase_date',
        'purchase_cost',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'depreciation_rate',
        'depreciation_start_date',
        'current_book_value',
        'status',
        'location',
        'serial_number',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'depreciation_start_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'current_book_value' => 'decimal:2',
            'depreciation_rate' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($asset) {
            if (empty($asset->asset_code)) {
                $asset->asset_code = static::generateAssetCode();
            }
            if (empty($asset->status)) {
                $asset->status = 'active';
            }
            if (empty($asset->current_book_value)) {
                $asset->current_book_value = $asset->purchase_cost;
            }
        });
    }

    public static function generateAssetCode(): string
    {
        $date = now()->format('Ymd');
        $lastAsset = static::where('asset_code', 'like', "AST-{$date}-%")
            ->orderBy('asset_code', 'desc')
            ->first();

        if ($lastAsset) {
            $lastNumber = (int) substr($lastAsset->asset_code, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "AST-{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class, 'fixed_asset_id');
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class, 'fixed_asset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMonthlyDepreciationAmount(): float
    {
        return match ($this->depreciation_method) {
            'declining_balance' => $this->calculateDecliningBalanceMonthly(),
            default => $this->calculateStraightLineMonthly(),
        };
    }

    protected function calculateStraightLineMonthly(): float
    {
        if ($this->useful_life_years <= 0) {
            return 0;
        }

        return round(($this->purchase_cost - $this->salvage_value) / $this->useful_life_years / 12, 2);
    }

    protected function calculateDecliningBalanceMonthly(): float
    {
        $rate = $this->depreciation_rate ?? 20;

        return round($this->current_book_value * ($rate / 100) / 12, 2);
    }
}
