<?php

namespace Workdo\AIDocument\Models;

use Illuminate\Database\Eloquent\Model;

class AIDocumentPrompt extends Model
{
    protected $table = 'ai_document_prompts';

    protected $fillable = [
        'name',
        'system_prompt',
        'prompt_template',
        'temperature',
        'max_tokens',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'is_active' => 'boolean',
    ];

    public function generations()
    {
        return $this->hasMany(AIDocumentGeneration::class, 'prompt_id');
    }
}
