<?php

namespace Workdo\API\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'token',
        'permissions',
        'last_used_at',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function logs()
    {
        return $this->hasMany(ApiLog::class, 'token_id');
    }
}
