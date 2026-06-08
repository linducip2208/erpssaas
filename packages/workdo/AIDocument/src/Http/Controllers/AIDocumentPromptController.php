<?php

namespace Workdo\AIDocument\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\AIDocument\Models\AIDocumentPrompt;
use Workdo\AIDocument\Models\AIDocumentGeneration;
use Workdo\AIDocument\Services\AIDocumentService;
use Illuminate\Support\Facades\Validator;

class AIDocumentPromptController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('manage-ai-document')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $prompts = AIDocumentPrompt::where('created_by', creatorId())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'prompts' => $prompts,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create-ai-document')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'system_prompt' => 'nullable|string',
            'prompt_template' => 'required|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:32000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $prompt = AIDocumentPrompt::create([
            'name' => $request->name,
            'system_prompt' => $request->system_prompt,
            'prompt_template' => $request->prompt_template,
            'temperature' => $request->temperature ?? 0.7,
            'max_tokens' => $request->max_tokens ?? 2000,
            'is_active' => $request->is_active ?? true,
            'created_by' => creatorId(),
        ]);

        return response()->json([
            'message' => __('Prompt created successfully.'),
            'prompt' => $prompt,
        ]);
    }

    public function show($id)
    {
        if (!Auth::user()->can('manage-ai-document')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $prompt = AIDocumentPrompt::where('created_by', creatorId())->findOrFail($id);

        return response()->json(['prompt' => $prompt]);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('edit-ai-document')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $prompt = AIDocumentPrompt::where('created_by', creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'system_prompt' => 'nullable|string',
            'prompt_template' => 'required|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:32000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $prompt->update([
            'name' => $request->name,
            'system_prompt' => $request->system_prompt,
            'prompt_template' => $request->prompt_template,
            'temperature' => $request->temperature ?? $prompt->temperature,
            'max_tokens' => $request->max_tokens ?? $prompt->max_tokens,
            'is_active' => $request->is_active ?? $prompt->is_active,
        ]);

        return response()->json([
            'message' => __('Prompt updated successfully.'),
            'prompt' => $prompt,
        ]);
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('delete-ai-document')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $prompt = AIDocumentPrompt::where('created_by', creatorId())->findOrFail($id);
        $prompt->delete();

        return response()->json(['message' => __('Prompt deleted successfully.')]);
    }
}
