<?php

namespace Workdo\AIDocument\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AIDocumentSettingsController extends Controller
{
    public function index()
    {
        $providers = [
            'openai' => [
                'name' => 'OpenAI',
                'models' => ['gpt-4o', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo'],
                'default_base_url' => 'https://api.openai.com',
            ],
            'anthropic' => [
                'name' => 'Anthropic (Claude)',
                'models' => ['claude-sonnet-4-5', 'claude-opus-4-1', 'claude-3-5-haiku-latest'],
                'default_base_url' => 'https://api.anthropic.com',
            ],
            'deepseek' => [
                'name' => 'DeepSeek',
                'models' => ['deepseek-chat', 'deepseek-reasoner'],
                'default_base_url' => 'https://api.deepseek.com',
            ],
            'groq' => [
                'name' => 'Groq',
                'models' => ['llama-3.1-8b-instant', 'llama-3.1-70b-versatile', 'mixtral-8x7b-32768'],
                'default_base_url' => 'https://api.groq.com/openai',
            ],
            'custom' => [
                'name' => 'Custom (OpenAI Compatible)',
                'models' => [],
                'default_base_url' => '',
            ],
        ];

        $settings = [
            'ai_document_provider' => company_setting('ai_document_provider'),
            'ai_document_api_key' => company_setting('ai_document_api_key'),
            'ai_document_base_url' => company_setting('ai_document_base_url'),
            'ai_document_default_model' => company_setting('ai_document_default_model'),
        ];

        return response()->json([
            'providers' => $providers,
            'settings' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('manage-ai-document-settings')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $validator = Validator::make($request->all(), [
            'settings.ai_document_provider' => 'required|string|in:openai,anthropic,deepseek,groq,custom',
            'settings.ai_document_api_key' => 'required|string|max:512',
            'settings.ai_document_base_url' => 'nullable|string|max:512',
            'settings.ai_document_default_model' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with('error', __('Validation failed'));
        }

        $settings = $request->input('settings', []);
        try {
            foreach ($settings as $key => $value) {
                setSetting($key, $value);
            }

            return redirect()->back()->with('success', __('AI Document settings saved successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update AI Document settings: ') . $e->getMessage());
        }
    }
}
