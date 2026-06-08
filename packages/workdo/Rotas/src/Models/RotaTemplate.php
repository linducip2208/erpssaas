<?php

namespace Workdo\Rotas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class RotaTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shifts()
    {
        return $this->hasMany(RotaTemplateShift::class, 'template_id');
    }

    public function rotas()
    {
        return $this->hasMany(Rota::class, 'template_id');
    }
}
