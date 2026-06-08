<?php

namespace Workdo\AIDocument\Services;

use Illuminate\Support\Facades\Http;
use Workdo\AIDocument\Models\AIDocumentPrompt;
use Workdo\AIDocument\Models\AIDocumentGeneration;

class AIDocumentService
{
    protected function getSettings(): array
    {
        return [
            'provider' => company_setting('ai_document_provider'),
            'api_key' => company_setting('ai_document_api_key'),
            'base_url' => company_setting('ai_document_base_url'),
            'default_model' => company_setting('ai_document_default_model'),
        ];
    }

    public function generate(AIDocumentPrompt $prompt, array $inputData, ?string $modelOverride = null): AIDocumentGeneration
    {
        $settings = $this->getSettings();

        if (empty($settings['provider']) || empty($settings['api_key'])) {
            throw new \Exception('AI Document configuration not found. Please configure provider and API key in settings.');
        }

        $generation = AIDocumentGeneration::create([
            'prompt_id' => $prompt->id,
            'input_data' => $inputData,
            'status' => 'processing',
            'model_used' => $modelOverride ?: $settings['default_model'],
            'created_by' => auth()->id(),
        ]);

        try {
            $systemPrompt = $prompt->system_prompt;
            $userPrompt = $this->buildPrompt($prompt->prompt_template, $inputData);
            $model = $modelOverride ?: $settings['default_model'] ?: 'gpt-3.5-turbo';
            $temperature = (float) $prompt->temperature;
            $maxTokens = (int) $prompt->max_tokens;

            $result = $this->callProvider(
                $settings['provider'],
                $settings['api_key'],
                $settings['base_url'],
                $model,
                $systemPrompt,
                $userPrompt,
                $temperature,
                $maxTokens
            );

            $generation->update([
                'output_content' => $result['content'],
                'input_tokens' => $result['input_tokens'] ?? 0,
                'output_tokens' => $result['output_tokens'] ?? 0,
                'model_used' => $model,
                'status' => 'completed',
            ]);

            return $generation;
        } catch (\Exception $e) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function buildPrompt(string $template, array $inputData): string
    {
        $prompt = $template;
        foreach ($inputData as $key => $value) {
            $prompt = str_replace('{' . $key . '}', (string) $value, $prompt);
        }
        return $prompt;
    }

    protected function callProvider(string $provider, string $apiKey, ?string $baseUrl, string $model, ?string $systemPrompt, string $userPrompt, float $temperature, int $maxTokens): array
    {
        return match ($provider) {
            'openai', 'deepseek', 'groq', 'custom' => $this->callOpenAICompatible($apiKey, $baseUrl, $model, $systemPrompt, $userPrompt, $temperature, $maxTokens),
            'anthropic' => $this->callAnthropicFormat($apiKey, $baseUrl, $model, $systemPrompt, $userPrompt, $temperature, $maxTokens),
            default => throw new \Exception("Unsupported AI provider: {$provider}"),
        };
    }

    protected function callOpenAICompatible(string $apiKey, ?string $baseUrl, string $model, ?string $systemPrompt, string $userPrompt, float $temperature, int $maxTokens): array
    {
        $url = $baseUrl ? rtrim($baseUrl, '/') . '/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions';

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'content' => $data['choices'][0]['message']['content'] ?? '',
                'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
            ];
        }

        throw new \Exception('AI API error: ' . $response->body());
    }

    protected function callAnthropicFormat(string $apiKey, ?string $baseUrl, string $model, ?string $systemPrompt, string $userPrompt, float $temperature, int $maxTokens): array
    {
        $url = $baseUrl ? rtrim($baseUrl, '/') . '/v1/messages' : 'https://api.anthropic.com/v1/messages';

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'user', 'content' => $systemPrompt . "\n\n" . $userPrompt];
        } else {
            $messages[] = ['role' => 'user', 'content' => $userPrompt];
        }

        $body = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => $messages,
        ];

        if ($systemPrompt) {
            $body['system'] = $systemPrompt;
            $body['messages'] = [['role' => 'user', 'content' => $userPrompt]];
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->post($url, $body);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'content' => $data['content'][0]['text'] ?? '',
                'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            ];
        }

        throw new \Exception('Anthropic API error: ' . $response->body());
    }
}
