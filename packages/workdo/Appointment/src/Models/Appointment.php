<?php

namespace Workdo\Appointment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type_id',
        'start_datetime',
        'end_datetime',
        'attendee_name',
        'attendee_email',
        'attendee_phone',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
        ];
    }

    public function type()
    {
        return $this->belongsTo(AppointmentType::class, 'type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
