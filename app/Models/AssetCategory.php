<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'asset_category_id');
    }
}
