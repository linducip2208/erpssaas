<?php

namespace Workdo\CustomField\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomFieldGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target_module',
        'target_type',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function fields()
    {
        return $this->hasMany(CustomField::class, 'group_id');
    }
}
