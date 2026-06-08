<?php

namespace Workdo\AIDocument\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\AIDocument\Models\AIDocumentPrompt;
use Workdo\AIDocument\Models\AIDocumentGeneration;
use Workdo\AIDocument\Services\AIDocumentService;
use Illuminate\Support\Facades\Validator;

class AIDocumentController extends Controller
{
    protected $aiDocumentService;

    public function __construct(AIDocumentService $aiDocumentService)
    {
        $this->aiDocumentService = $aiDocumentService;
    }

    public function generate(Request $request)
    {
        if (!Auth::user()->can('manage-ai-document')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $validator = Validator::make($request->all(), [
            'prompt_id' => 'required|exists:ai_document_prompts,id',
            'input_data' => 'nullable|array',
            'model' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $prompt = AIDocumentPrompt::where('created_by', creatorId())->findOrFail($request->prompt_id);
            $inputData = $request->input('input_data', []);
            $modelOverride = $request->input('model');

            $generation = $this->aiDocumentService->generate($prompt, $inputData, $modelOverride);

            return response()->json([
                'success' => true,
                'message' => __('Document generated successfully!'),
                'generation' => $generation->load('prompt'),
            ]);
        } catch (\Exception $e) {
            \Log::error('AI Document Generation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $this->getFormattedErrorMessage($e->getMessage()),
            ], 422);
        }
    }

    public function generations()
    {
        if (!Auth::user()->can('manage-ai-document')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $generations = AIDocumentGeneration::with('prompt')
            ->where('created_by', creatorId())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json(['generations' => $generations]);
    }

    public function showGeneration($id)
    {
        if (!Auth::user()->can('manage-ai-document')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $generation = AIDocumentGeneration::with('prompt')
            ->where('created_by', creatorId())
            ->findOrFail($id);

        return response()->json(['generation' => $generation]);
    }

    protected function getFormattedErrorMessage($message)
    {
        if (str_contains($message, 'configuration')) {
            return __('Please configure AI Document settings first (Provider, API Key).');
        }
        if (str_contains($message, 'API key') || str_contains($message, 'authentication') || str_contains($message, 'unauthorized')) {
            return __('Invalid API key. Please check your AI Document configuration.');
        }
        if (str_contains($message, 'rate limit') || str_contains($message, 'quota')) {
            return __('API rate limit exceeded. Please try again later.');
        }
        if (str_contains($message, 'Unsupported AI provider')) {
            return __('Unsupported AI provider. Please select a valid provider in settings.');
        }
        if (str_contains($message, 'API error') || str_contains($message, 'HTTP error')) {
            return __('AI service encountered an error. Please try again later.');
        }
        return __('Failed to generate document. Please try again or contact support.');
    }
}
