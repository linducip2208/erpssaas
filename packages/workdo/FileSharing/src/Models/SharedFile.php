<?php

namespace Workdo\FileSharing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class SharedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    public function folder()
    {
        return $this->belongsTo(SharedFolder::class, 'folder_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sharedLinks()
    {
        return $this->hasMany(SharedLink::class, 'file_id');
    }
}
