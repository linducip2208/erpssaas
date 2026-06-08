<?php

namespace Workdo\Appointment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class AppointmentAvailability extends Model
{
    use HasFactory;

    protected $table = 'appointment_availability';

    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
