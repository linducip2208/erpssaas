<?php

namespace Workdo\AIDocument\Models;

use Illuminate\Database\Eloquent\Model;

class AIDocumentGeneration extends Model
{
    protected $table = 'ai_document_generations';

    protected $fillable = [
        'prompt_id',
        'input_data',
        'output_content',
        'input_tokens',
        'output_tokens',
        'model_used',
        'status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'input_data' => 'array',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'status' => 'string',
    ];

    public function prompt()
    {
        return $this->belongsTo(AIDocumentPrompt::class, 'prompt_id');
    }
}
