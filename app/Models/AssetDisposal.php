<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    protected $fillable = [
        'fixed_asset_id',
        'disposal_date',
        'sale_amount',
        'book_value',
        'gain_loss',
        'disposal_method',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'disposal_date' => 'date',
            'sale_amount' => 'decimal:2',
            'book_value' => 'decimal:2',
            'gain_loss' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($disposal) {
            if (empty($disposal->gain_loss)) {
                $disposal->gain_loss = ($disposal->sale_amount ?? 0) - $disposal->book_value;
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
