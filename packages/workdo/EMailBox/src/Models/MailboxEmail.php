<?php

namespace Workdo\EMailBox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MailboxEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'mailbox_id',
        'message_id',
        'from_email',
        'to_email',
        'subject',
        'body',
        'received_at',
        'is_read',
        'is_starred',
        'folder',
        'attachments',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'attachments' => 'array',
        'received_at' => 'datetime',
    ];

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id');
    }
}
