<?php

namespace Workdo\Rotas\Http\Controllers;

use Workdo\Rotas\Models\Rota;
use Workdo\Rotas\Models\RotaAssignment;
use Workdo\Rotas\Models\RotaTemplate;
use Workdo\Rotas\Models\RotaTemplateShift;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;

class RotaController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-rotas')) {
            $rotas = Rota::where('created_by', creatorId())
                ->with(['template', 'assignments.user'])
                ->when(request('status') && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->latest()
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $templates = RotaTemplate::where('created_by', creatorId())->select('id', 'name')->get();
            $users = User::where('created_by', creatorId())->select('id', 'name')->get();

            return Inertia::render('Rotas/Index', [
                'rotas' => $rotas,
                'templates' => $templates,
                'users' => $users,
                'auth' => Auth::user(),
            ]);
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-rotas')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'template_id' => 'nullable|exists:rota_templates,id',
            ]);

            $rota = Rota::create([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'template_id' => $request->template_id,
                'status' => 'draft',
                'created_by' => creatorId(),
            ]);

            if ($request->generate_from_template && $rota->template_id) {
                $this->generateFromTemplate($rota);
            }

            return redirect()->back()->with('success', __('Rota created successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->can('edit-rotas')) {
            $rota = Rota::where('created_by', creatorId())->findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'nullable|string',
            ]);

            $rota->update($request->only(['name', 'start_date', 'end_date', 'status']));

            return redirect()->back()->with('success', __('Rota updated successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-rotas')) {
            $rota = Rota::where('created_by', creatorId())->findOrFail($id);
            $rota->delete();

            return redirect()->back()->with('success', __('Rota deleted successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function show($id)
    {
        if (Auth::user()->can('view-rotas')) {
            $rota = Rota::where('created_by', creatorId())
                ->with(['template.shifts', 'assignments.user'])
                ->findOrFail($id);

            $users = User::where('created_by', creatorId())->select('id', 'name')->get();

            return Inertia::render('Rotas/Assign', [
                'rota' => $rota,
                'users' => $users,
                'auth' => Auth::user(),
            ]);
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function generateFromTemplate($rotaOrId)
    {
        $rota = $rotaOrId instanceof Rota ? $rotaOrId : Rota::where('created_by', creatorId())->findOrFail($rotaOrId);

        $template = $rota->template;
        if (!$template) {
            return;
        }

        $shifts = $template->shifts;
        $start = \Carbon\Carbon::parse($rota->start_date);
        $end = \Carbon\Carbon::parse($rota->end_date);

        $rota->assignments()->delete();

        $current = $start->copy();
        while ($current->lte($end)) {
            foreach ($shifts as $shift) {
                if ($shift->day_of_week == $current->dayOfWeek) {
                    RotaAssignment::create([
                        'rota_id' => $rota->id,
                        'user_id' => null,
                        'date' => $current->toDateString(),
                        'start_time' => $shift->start_time,
                        'end_time' => $shift->end_time,
                        'status' => 'unassigned',
                    ]);
                }
            }
            $current->addDay();
        }
    }

    public function generate(Request $request, $id)
    {
        if (Auth::user()->can('create-rota-assignments')) {
            $this->generateFromTemplate($id);

            return redirect()->back()->with('success', __('Rota generated from template.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function assignEmployee(Request $request)
    {
        if (Auth::user()->can('create-rota-assignments')) {
            $request->validate([
                'assignment_id' => 'required|exists:rota_assignments,id',
                'user_id' => 'required|exists:users,id',
            ]);

            $assignment = RotaAssignment::whereHas('rota', function ($q) {
                $q->where('created_by', creatorId());
            })->findOrFail($request->assignment_id);

            $assignment->update([
                'user_id' => $request->user_id,
                'status' => 'assigned',
            ]);

            return redirect()->back()->with('success', __('Employee assigned successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function bulkAssign(Request $request, $id)
    {
        if (Auth::user()->can('create-rota-assignments')) {
            $rota = Rota::where('created_by', creatorId())->findOrFail($id);

            $request->validate([
                'assignments' => 'required|array',
                'assignments.*.assignment_id' => 'required|exists:rota_assignments,id',
                'assignments.*.user_id' => 'required|exists:users,id',
            ]);

            foreach ($request->assignments as $assign) {
                RotaAssignment::where('id', $assign['assignment_id'])
                    ->where('rota_id', $rota->id)
                    ->update([
                        'user_id' => $assign['user_id'],
                        'status' => 'assigned',
                    ]);
            }

            return redirect()->back()->with('success', __('Employees assigned successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function updateAssignment(Request $request, $id)
    {
        if (Auth::user()->can('edit-rota-assignments')) {
            $assignment = RotaAssignment::whereHas('rota', function ($q) {
                $q->where('created_by', creatorId());
            })->findOrFail($id);

            $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'status' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $assignment->update($request->only(['user_id', 'start_time', 'end_time', 'status', 'notes']));

            return redirect()->back()->with('success', __('Assignment updated successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function deleteAssignment($id)
    {
        if (Auth::user()->can('delete-rota-assignments')) {
            $assignment = RotaAssignment::whereHas('rota', function ($q) {
                $q->where('created_by', creatorId());
            })->findOrFail($id);

            $assignment->delete();

            return redirect()->back()->with('success', __('Assignment deleted successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }
}
