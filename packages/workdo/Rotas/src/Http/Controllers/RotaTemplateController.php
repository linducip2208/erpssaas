<?php

namespace Workdo\Rotas\Http\Controllers;

use Workdo\Rotas\Models\RotaTemplate;
use Workdo\Rotas\Models\RotaTemplateShift;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RotaTemplateController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-rota-templates')) {
            $templates = RotaTemplate::where('created_by', creatorId())
                ->with(['shifts'])
                ->latest()
                ->get();

            return Inertia::render('Rotas/Templates', [
                'templates' => $templates,
                'auth' => Auth::user(),
            ]);
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-rota-templates')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'shifts' => 'nullable|array',
                'shifts.*.day_of_week' => 'required|integer|min:0|max:6',
                'shifts.*.start_time' => 'required|date_format:H:i',
                'shifts.*.end_time' => 'required|date_format:H:i|after:shifts.*.start_time',
                'shifts.*.role' => 'nullable|string|max:255',
            ]);

            $template = RotaTemplate::create([
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => creatorId(),
            ]);

            if ($request->has('shifts')) {
                foreach ($request->shifts as $shift) {
                    RotaTemplateShift::create([
                        'template_id' => $template->id,
                        'day_of_week' => $shift['day_of_week'],
                        'start_time' => $shift['start_time'],
                        'end_time' => $shift['end_time'],
                        'role' => $shift['role'] ?? null,
                        'created_by' => creatorId(),
                    ]);
                }
            }

            return redirect()->back()->with('success', __('Template created successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->can('edit-rota-templates')) {
            $template = RotaTemplate::where('created_by', creatorId())->findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $template->update($request->only(['name', 'description']));

            return redirect()->back()->with('success', __('Template updated successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-rota-templates')) {
            $template = RotaTemplate::where('created_by', creatorId())->findOrFail($id);
            $template->delete();

            return redirect()->back()->with('success', __('Template deleted successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }
}
