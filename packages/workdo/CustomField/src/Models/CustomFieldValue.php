<?php

namespace Workdo\CustomField\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_id',
        'target_id',
        'value',
    ];

    public function field()
    {
        return $this->belongsTo(CustomField::class, 'field_id');
    }
}
