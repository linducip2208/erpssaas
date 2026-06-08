<?php

namespace Workdo\FileSharing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class SharedLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'token',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function file()
    {
        return $this->belongsTo(SharedFile::class, 'file_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return now()->greaterThan($this->expires_at);
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
}
