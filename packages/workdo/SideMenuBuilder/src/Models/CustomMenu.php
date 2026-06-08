<?php

namespace Workdo\SideMenuBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'href',
        'icon',
        'permission',
        'parent_id',
        'role_id',
        'sort_order',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(CustomMenu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(CustomMenu::class, 'parent_id')->orderBy('sort_order');
    }
}
