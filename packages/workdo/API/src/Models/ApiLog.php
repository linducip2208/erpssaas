<?php

namespace Workdo\API\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'token_id',
        'method',
        'endpoint',
        'request_data',
        'response_code',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function token()
    {
        return $this->belongsTo(ApiToken::class, 'token_id');
    }
}
