<?php

namespace Workdo\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'trigger_module',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function actions()
    {
        return $this->hasMany(WorkflowAction::class, 'workflow_id')->orderBy('order');
    }

    public function logs()
    {
        return $this->hasMany(WorkflowLog::class, 'workflow_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
