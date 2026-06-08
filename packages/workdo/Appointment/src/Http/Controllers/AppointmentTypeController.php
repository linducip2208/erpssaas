<?php

namespace Workdo\Appointment\Http\Controllers;

use Workdo\Appointment\Models\AppointmentType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AppointmentTypeController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-appointment-types')) {
            $types = AppointmentType::where('created_by', creatorId())->latest()->get();

            return Inertia::render('Appointment/Types', [
                'types' => $types,
                'auth' => Auth::user(),
            ]);
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-appointment-types')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'duration_minutes' => 'required|integer|min:5',
                'color' => 'nullable|string|max:7',
            ]);

            AppointmentType::create([
                'name' => $request->name,
                'duration_minutes' => $request->duration_minutes,
                'color' => $request->color ?? '#6366f1',
                'is_active' => true,
                'created_by' => creatorId(),
            ]);

            return redirect()->back()->with('success', __('Appointment type created successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->can('edit-appointment-types')) {
            $type = AppointmentType::where('created_by', creatorId())->findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'duration_minutes' => 'required|integer|min:5',
                'color' => 'nullable|string|max:7',
                'is_active' => 'boolean',
            ]);

            $type->update($request->only(['name', 'duration_minutes', 'color', 'is_active']));

            return redirect()->back()->with('success', __('Appointment type updated successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-appointment-types')) {
            $type = AppointmentType::where('created_by', creatorId())->findOrFail($id);
            $type->delete();

            return redirect()->back()->with('success', __('Appointment type deleted successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }
}
