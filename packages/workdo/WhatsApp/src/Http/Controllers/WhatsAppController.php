<?php

namespace Workdo\WhatsApp\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Workdo\WhatsApp\Models\WhatsAppTemplate;
use Workdo\WhatsApp\Models\WhatsAppLog;

class WhatsAppController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('manage-whatsapp')) {
            return back()->with('error', __('Permission denied'));
        }

        $stats = [
            'templates_count' => WhatsAppTemplate::where('created_by', creatorId())->count(),
            'messages_sent' => WhatsAppLog::where('sent_by', Auth::id())->count(),
            'messages_today' => WhatsAppLog::where('sent_by', Auth::id())->whereDate('created_at', today())->count(),
            'active_templates' => WhatsAppTemplate::where('created_by', creatorId())->where('status', 'active')->count(),
        ];

        return response()->json(['stats' => $stats]);
    }

    public function templates(Request $request)
    {
        if (!Auth::user()->can('manage-whatsapp')) {
            return back()->with('error', __('Permission denied'));
        }

        $templates = WhatsAppTemplate::where('created_by', creatorId())
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json(['templates' => $templates]);
    }

    public function templateStore(Request $request)
    {
        if (!Auth::user()->can('manage-whatsapp')) {
            return back()->with('error', __('Permission denied'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'language' => 'required|string|max:10',
            'category' => 'nullable|string|max:100',
            'template_id' => 'nullable|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', __('Validation failed'));
        }

        WhatsAppTemplate::create([
            'name' => $request->name,
            'language' => $request->language,
            'category' => $request->category,
            'template_id' => $request->template_id,
            'content' => $request->content,
            'status' => 'active',
            'variables' => $request->variables,
            'created_by' => creatorId(),
        ]);

        return back()->with('success', __('WhatsApp template created successfully.'));
    }

    public function templateUpdate(Request $request, $id)
    {
        if (!Auth::user()->can('manage-whatsapp')) {
            return back()->with('error', __('Permission denied'));
        }

        $template = WhatsAppTemplate::where('created_by', creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'language' => 'required|string|max:10',
            'category' => 'nullable|string|max:100',
            'template_id' => 'nullable|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', __('Validation failed'));
        }

        $template->update($request->only(['name', 'language', 'category', 'template_id', 'content', 'variables', 'status']));

        return back()->with('success', __('WhatsApp template updated successfully.'));
    }

    public function templateDestroy($id)
    {
        if (!Auth::user()->can('manage-whatsapp')) {
            return back()->with('error', __('Permission denied'));
        }

        $template = WhatsAppTemplate::where('created_by', creatorId())->findOrFail($id);
        $template->delete();

        return back()->with('success', __('WhatsApp template deleted successfully.'));
    }

    public function send(Request $request)
    {
        if (!Auth::user()->can('manage-whatsapp')) {
            return back()->with('error', __('Permission denied'));
        }

        $validator = Validator::make($request->all(), [
            'to_number' => 'required|string|max:20',
            'message' => 'required_without:template_id|string|max:4096',
            'template_id' => 'nullable|exists:whatsapp_templates,id',
            'variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', __('Validation failed'));
        }

        $baseUrl = getSetting('whatsapp_base_url', creatorId());
        $apiKey = getSetting('whatsapp_api_key', creatorId());
        $phoneNumberId = getSetting('whatsapp_phone_number_id', creatorId());

        $message = $request->message;

        if ($request->template_id) {
            $template = WhatsAppTemplate::find($request->template_id);
            $message = $template->content;
            if ($request->variables && $template->variables) {
                foreach ($template->variables as $i => $var) {
                    $message = str_replace('{{' . ($i + 1) . '}}', $request->variables[$i] ?? '', $message);
                }
            }
        }

        $log = WhatsAppLog::create([
            'to_number' => $request->to_number,
            'message' => $message,
            'template_id' => $request->template_id,
            'status' => 'pending',
            'sent_by' => Auth::id(),
        ]);

        try {
            if ($baseUrl && $apiKey) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post(rtrim($baseUrl, '/') . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $request->to_number,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

                $log->update([
                    'status' => $response->successful() ? 'sent' : 'failed',
                    'response' => $response->json(),
                    'error_message' => $response->failed() ? ($response->json()['error']['message'] ?? 'Unknown error') : null,
                ]);
            } else {
                $log->update([
                    'status' => 'sent',
                    'response' => ['message' => 'WhatsApp API not configured, simulated send'],
                ]);
            }
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        if ($log->status === 'sent') {
            return back()->with('success', __('WhatsApp message sent successfully.'));
        }

        return back()->with('error', __('Failed to send WhatsApp message: ') . $log->error_message);
    }

    public function logs(Request $request)
    {
        if (!Auth::user()->can('manage-whatsapp')) {
            return back()->with('error', __('Permission denied'));
        }

        $logs = WhatsAppLog::with(['template', 'sender'])
            ->where('sent_by', Auth::id())
            ->when($request->search, function ($query, $search) {
                $query->where('to_number', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json(['logs' => $logs]);
    }

    public function settings(Request $request)
    {
        if (!Auth::user()->can('manage-whatsapp')) {
            return back()->with('error', __('Permission denied'));
        }

        if ($request->isMethod('get')) {
            $settings = [
                'whatsapp_base_url' => getSetting('whatsapp_base_url', creatorId()),
                'whatsapp_api_key' => getSetting('whatsapp_api_key', creatorId()),
                'whatsapp_phone_number_id' => getSetting('whatsapp_phone_number_id', creatorId()),
            ];

            return response()->json(['settings' => $settings]);
        }

        $validator = Validator::make($request->all(), [
            'whatsapp_base_url' => 'required|url|max:255',
            'whatsapp_api_key' => 'required|string|max:255',
            'whatsapp_phone_number_id' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', __('Validation failed'));
        }

        try {
            setSetting('whatsapp_base_url', $request->whatsapp_base_url, creatorId());
            setSetting('whatsapp_api_key', $request->whatsapp_api_key, creatorId());
            setSetting('whatsapp_phone_number_id', $request->whatsapp_phone_number_id, creatorId());

            return back()->with('success', __('WhatsApp settings saved successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to save WhatsApp settings: ') . $e->getMessage());
        }
    }
}
