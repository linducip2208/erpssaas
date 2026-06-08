<?php

namespace Workdo\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkflowLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'trigger_data',
        'action_data',
        'status',
        'executed_at',
        'error_message',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'action_data' => 'array',
        'executed_at' => 'datetime',
    ];

    public $timestamps = false;

    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }
}
