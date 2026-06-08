<?php

namespace Workdo\ProjectTemplate\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplateMilestone extends Model
{
    protected $table = 'project_template_milestones';

    protected $fillable = [
        'template_id',
        'name',
        'description',
        'order',
        'due_days_offset',
    ];

    protected $casts = [
        'order' => 'integer',
        'due_days_offset' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }
}
