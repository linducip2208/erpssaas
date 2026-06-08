<?php

namespace Workdo\CustomField\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'name',
        'field_type',
        'label',
        'placeholder',
        'is_required',
        'options',
        'default_value',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'options' => 'array',
        ];
    }

    public function group()
    {
        return $this->belongsTo(CustomFieldGroup::class, 'group_id');
    }

    public function values()
    {
        return $this->hasMany(CustomFieldValue::class, 'field_id');
    }
}
