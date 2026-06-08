export interface AIDocumentPrompt {
    id: number;
    name: string;
    system_prompt?: string;
    prompt_template: string;
    temperature: number;
    max_tokens: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface AIDocumentGeneration {
    id: number;
    prompt_id: number;
    input_data?: Record<string, any>;
    output_content?: string;
    input_tokens: number;
    output_tokens: number;
    model_used?: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    error_message?: string;
    created_at: string;
    prompt?: AIDocumentPrompt;
}

export interface AIDocumentProvider {
    name: string;
    models: string[];
    default_base_url: string;
}

export interface AIDocumentSettings {
    ai_document_provider: string;
    ai_document_api_key: string;
    ai_document_base_url: string;
    ai_document_default_model: string;
}
