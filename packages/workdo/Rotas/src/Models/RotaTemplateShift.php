<?php

namespace Workdo\Rotas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class RotaTemplateShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'day_of_week',
        'start_time',
        'end_time',
        'role',
        'created_by',
    ];

    public function template()
    {
        return $this->belongsTo(RotaTemplate::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
