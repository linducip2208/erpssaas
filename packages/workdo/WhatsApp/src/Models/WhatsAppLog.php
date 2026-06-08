<?php

namespace Workdo\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class WhatsAppLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'to_number',
        'message',
        'template_id',
        'status',
        'response',
        'error_message',
        'sent_by',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public $timestamps = false;

    public function template()
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'template_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
