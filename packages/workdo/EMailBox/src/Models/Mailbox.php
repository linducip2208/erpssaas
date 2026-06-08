<?php

namespace Workdo\EMailBox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Mailbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'username',
        'password',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'password',
    ];

    public function emails()
    {
        return $this->hasMany(MailboxEmail::class, 'mailbox_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
