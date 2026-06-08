<?php

namespace Workdo\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Workdo\Workflow\Models\Workflow;
use Workdo\Workflow\Models\WorkflowAction;
use Workdo\Workflow\Models\WorkflowLog;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage-workflows')) {
            return back()->with('error', __('Permission denied'));
        }

        $workflows = Workflow::withCount(['actions', 'logs'])
            ->where('created_by', creatorId())
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json(['workflows' => $workflows]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('manage-workflows')) {
            return back()->with('error', __('Permission denied'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string|max:100',
            'trigger_module' => 'required|string|max:100',
            'actions' => 'nullable|array',
            'actions.*.action_type' => 'required|string|max:100',
            'actions.*.action_module' => 'required|string|max:100',
            'actions.*.config' => 'nullable|array',
            'actions.*.order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', __('Validation failed'));
        }

        $workflow = Workflow::create([
            'name' => $request->name,
            'description' => $request->description,
            'trigger_type' => $request->trigger_type,
            'trigger_module' => $request->trigger_module,
            'is_active' => true,
            'created_by' => creatorId(),
        ]);

        if ($request->actions) {
            foreach ($request->actions as $index => $action) {
                WorkflowAction::create([
                    'workflow_id' => $workflow->id,
                    'order' => $action['order'] ?? $index,
                    'action_type' => $action['action_type'],
                    'action_module' => $action['action_module'],
                    'config' => $action['config'] ?? null,
                ]);
            }
        }

        return back()->with('success', __('Workflow created successfully.'));
    }

    public function show($id)
    {
        if (!Auth::user()->can('manage-workflows')) {
            return back()->with('error', __('Permission denied'));
        }

        $workflow = Workflow::with('actions')
            ->where('created_by', creatorId())
            ->findOrFail($id);

        return response()->json(['workflow' => $workflow]);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('manage-workflows')) {
            return back()->with('error', __('Permission denied'));
        }

        $workflow = Workflow::where('created_by', creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string|max:100',
            'trigger_module' => 'required|string|max:100',
            'actions' => 'nullable|array',
            'actions.*.action_type' => 'required|string|max:100',
            'actions.*.action_module' => 'required|string|max:100',
            'actions.*.config' => 'nullable|array',
            'actions.*.order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', __('Validation failed'));
        }

        $workflow->update([
            'name' => $request->name,
            'description' => $request->description,
            'trigger_type' => $request->trigger_type,
            'trigger_module' => $request->trigger_module,
        ]);

        $workflow->actions()->delete();
        if ($request->actions) {
            foreach ($request->actions as $index => $action) {
                WorkflowAction::create([
                    'workflow_id' => $workflow->id,
                    'order' => $action['order'] ?? $index,
                    'action_type' => $action['action_type'],
                    'action_module' => $action['action_module'],
                    'config' => $action['config'] ?? null,
                ]);
            }
        }

        return back()->with('success', __('Workflow updated successfully.'));
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('manage-workflows')) {
            return back()->with('error', __('Permission denied'));
        }

        $workflow = Workflow::where('created_by', creatorId())->findOrFail($id);
        $workflow->delete();

        return back()->with('success', __('Workflow deleted successfully.'));
    }

    public function toggle($id)
    {
        if (!Auth::user()->can('manage-workflows')) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Permission denied'], 403);
            }
            return back()->with('error', __('Permission denied'));
        }

        $workflow = Workflow::where('created_by', creatorId())->findOrFail($id);
        $workflow->update(['is_active' => !$workflow->is_active]);

        return back()->with('success', $workflow->is_active
            ? __('Workflow activated successfully.')
            : __('Workflow deactivated successfully.'));
    }

    public function execute(Request $request, $id)
    {
        if (!Auth::user()->can('manage-workflows')) {
            return back()->with('error', __('Permission denied'));
        }

        $workflow = Workflow::with('actions')
            ->where('created_by', creatorId())
            ->where('is_active', true)
            ->findOrFail($id);

        $triggerData = $request->input('trigger_data', []);

        $results = [];
        foreach ($workflow->actions as $action) {
            try {
                WorkflowLog::create([
                    'workflow_id' => $workflow->id,
                    'trigger_data' => $triggerData,
                    'action_data' => $action->config ?? [],
                    'status' => 'completed',
                    'executed_at' => now(),
                ]);
                $results[$action->id] = ['status' => 'completed'];
            } catch (\Exception $e) {
                WorkflowLog::create([
                    'workflow_id' => $workflow->id,
                    'trigger_data' => $triggerData,
                    'action_data' => $action->config ?? [],
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'executed_at' => now(),
                ]);
                $results[$action->id] = ['status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return response()->json(['results' => $results]);
    }

    public function logs(Request $request)
    {
        if (!Auth::user()->can('manage-workflows')) {
            return back()->with('error', __('Permission denied'));
        }

        $logs = WorkflowLog::with('workflow')
            ->whereHas('workflow', function ($query) {
                $query->where('created_by', creatorId());
            })
            ->when($request->workflow_id, function ($query, $workflowId) {
                $query->where('workflow_id', $workflowId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json(['logs' => $logs]);
    }
}
