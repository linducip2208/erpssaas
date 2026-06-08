<?php

namespace Workdo\ProjectTemplate\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Workdo\ProjectTemplate\Models\ProjectTemplate;
use Workdo\ProjectTemplate\Models\ProjectTemplateTask;
use Workdo\ProjectTemplate\Models\ProjectTemplateMilestone;
use Workdo\Taskly\Models\Project;
use Workdo\Taskly\Models\ProjectMilestone;
use Workdo\Taskly\Models\ProjectTask;

class ProjectTemplateController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('manage-project-template')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $templates = ProjectTemplate::with(['tasks', 'milestones'])
            ->where('created_by', creatorId())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json(['templates' => $templates]);
    }

    public function show($id)
    {
        if (!Auth::user()->can('manage-project-template')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $template = ProjectTemplate::with(['tasks', 'milestones'])
            ->where('created_by', creatorId())
            ->findOrFail($id);

        return response()->json(['template' => $template]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create-project-template')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'tasks' => 'nullable|array',
            'tasks.*.title' => 'required_with:tasks|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.priority' => 'nullable|string|in:Low,Medium,High,Urgent',
            'tasks.*.order' => 'nullable|integer',
            'tasks.*.estimated_hours' => 'nullable|numeric|min:0',
            'milestones' => 'nullable|array',
            'milestones.*.name' => 'required_with:milestones|string|max:255',
            'milestones.*.description' => 'nullable|string',
            'milestones.*.order' => 'nullable|integer',
            'milestones.*.due_days_offset' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template = ProjectTemplate::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
            'created_by' => creatorId(),
        ]);

        if ($request->has('tasks')) {
            foreach ($request->tasks as $index => $taskData) {
                $template->tasks()->create([
                    'title' => $taskData['title'],
                    'description' => $taskData['description'] ?? null,
                    'priority' => $taskData['priority'] ?? 'Low',
                    'order' => $taskData['order'] ?? ($index + 1),
                    'estimated_hours' => $taskData['estimated_hours'] ?? null,
                ]);
            }
        }

        if ($request->has('milestones')) {
            foreach ($request->milestones as $index => $milestoneData) {
                $template->milestones()->create([
                    'name' => $milestoneData['name'],
                    'description' => $milestoneData['description'] ?? null,
                    'order' => $milestoneData['order'] ?? ($index + 1),
                    'due_days_offset' => $milestoneData['due_days_offset'] ?? 7,
                ]);
            }
        }

        $template->load(['tasks', 'milestones']);

        return response()->json([
            'message' => __('Template created successfully.'),
            'template' => $template,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('edit-project-template')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $template = ProjectTemplate::where('created_by', creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? $template->is_active,
        ]);

        $template->load(['tasks', 'milestones']);

        return response()->json([
            'message' => __('Template updated successfully.'),
            'template' => $template,
        ]);
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('delete-project-template')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $template = ProjectTemplate::where('created_by', creatorId())->findOrFail($id);
        $template->delete();

        return response()->json(['message' => __('Template deleted successfully.')]);
    }

    public function duplicate($id)
    {
        if (!Auth::user()->can('duplicate-project-template')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $original = ProjectTemplate::with(['tasks', 'milestones'])
            ->where('created_by', creatorId())
            ->findOrFail($id);

        $duplicate = $original->replicate();
        $duplicate->name = $original->name . ' (' . __('Copy') . ')';
        $duplicate->save();

        foreach ($original->tasks as $task) {
            $newTask = $task->replicate();
            $newTask->template_id = $duplicate->id;
            $newTask->save();
        }

        foreach ($original->milestones as $milestone) {
            $newMilestone = $milestone->replicate();
            $newMilestone->template_id = $duplicate->id;
            $newMilestone->save();
        }

        $duplicate->load(['tasks', 'milestones']);

        return response()->json([
            'message' => __('Template duplicated successfully.'),
            'template' => $duplicate,
        ]);
    }

    public function createProject(Request $request, $id)
    {
        if (!Auth::user()->can('create-project') || !Auth::user()->can('manage-project-template')) {
            return response()->json(['error' => __('Permission denied')], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template = ProjectTemplate::with(['tasks', 'milestones'])
            ->where('created_by', creatorId())
            ->findOrFail($id);

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description ?? $template->description,
            'start_date' => $request->start_date ?? now()->format('Y-m-d'),
            'end_date' => $request->end_date,
            'budget' => $request->budget,
            'status' => 'Ongoing',
            'creator_id' => Auth::id(),
            'created_by' => creatorId(),
        ]);

        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : now();

        // Create milestones from template
        foreach ($template->milestones as $milestone) {
            $dueDate = $startDate->copy()->addDays($milestone->due_days_offset);

            // Map to existing ProjectMilestone structure
            $projectMilestone = ProjectMilestone::create([
                'project_id' => $project->id,
                'title' => $milestone->name,
                'summary' => $milestone->description,
                'status' => 'Incomplete',
                'progress' => 0,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $dueDate->format('Y-m-d'),
            ]);

            // Find tasks that belong to this milestone by checking if they're associated
            // We'll create remaining tasks that don't have milestone association
        }

        // Create tasks from template
        foreach ($template->tasks as $task) {
            $taskStage = \Workdo\Taskly\Models\TaskStage::where('created_by', creatorId())
                ->orderBy('order')
                ->first();

            ProjectTask::create([
                'project_id' => $project->id,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority,
                'stage_id' => $taskStage?->id,
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);
        }

        return response()->json([
            'message' => __('Project created from template successfully.'),
            'project' => $project->load(['milestones', 'tasks']),
        ]);
    }
}
