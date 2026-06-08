<?php

namespace Workdo\FileSharing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class FolderPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'user_id',
        'permission',
    ];

    public function folder()
    {
        return $this->belongsTo(SharedFolder::class, 'folder_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
