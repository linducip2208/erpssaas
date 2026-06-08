<?php

namespace Workdo\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkflowAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'order',
        'action_type',
        'action_module',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }
}
