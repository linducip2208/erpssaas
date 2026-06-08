<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciation extends Model
{
    protected $fillable = [
        'fixed_asset_id',
        'depreciation_date',
        'amount',
        'book_value_before',
        'book_value_after',
        'accumulated_depreciation',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'depreciation_date' => 'date',
            'amount' => 'decimal:2',
            'book_value_before' => 'decimal:2',
            'book_value_after' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
