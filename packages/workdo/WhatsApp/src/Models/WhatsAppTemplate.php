<?php

namespace Workdo\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'language',
        'category',
        'template_id',
        'content',
        'status',
        'variables',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
