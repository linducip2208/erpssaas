<?php

namespace Workdo\Appointment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class AppointmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'duration_minutes',
        'color',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'type_id');
    }
}
