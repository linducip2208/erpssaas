<?php

namespace Workdo\Rotas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class RotaAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rota_id',
        'user_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function rota()
    {
        return $this->belongsTo(Rota::class, 'rota_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
