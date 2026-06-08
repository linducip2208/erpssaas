<?php

namespace Workdo\ProjectTemplate\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplateTask extends Model
{
    protected $table = 'project_template_tasks';

    protected $fillable = [
        'template_id',
        'title',
        'description',
        'priority',
        'order',
        'estimated_hours',
    ];

    protected $casts = [
        'estimated_hours' => 'decimal:2',
        'order' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }
}
