<?php

namespace Workdo\FileSharing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class SharedFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
        'created_by',
    ];

    public function parent()
    {
        return $this->belongsTo(SharedFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SharedFolder::class, 'parent_id');
    }

    public function files()
    {
        return $this->hasMany(SharedFile::class, 'folder_id');
    }

    public function permissions()
    {
        return $this->hasMany(FolderPermission::class, 'folder_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
