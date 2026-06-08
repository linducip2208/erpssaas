<?php

namespace Workdo\Appointment\Http\Controllers;

use Workdo\Appointment\Models\AppointmentAvailability;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;

class AppointmentAvailabilityController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-appointment-availability')) {
            $availabilities = AppointmentAvailability::where('created_by', creatorId())
                ->with('user')
                ->get();

            $users = User::where('created_by', creatorId())->select('id', 'name')->get();

            return Inertia::render('Appointment/Availability', [
                'availabilities' => $availabilities,
                'users' => $users,
                'auth' => Auth::user(),
            ]);
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-appointment-availability')) {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'day_of_week' => 'required|integer|min:0|max:6',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
            ]);

            AppointmentAvailability::create([
                'user_id' => $request->user_id,
                'day_of_week' => $request->day_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'created_by' => creatorId(),
            ]);

            return redirect()->back()->with('success', __('Availability added successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->can('edit-appointment-availability')) {
            $availability = AppointmentAvailability::where('created_by', creatorId())->findOrFail($id);

            $request->validate([
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
            ]);

            $availability->update($request->only(['start_time', 'end_time']));

            return redirect()->back()->with('success', __('Availability updated successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-appointment-availability')) {
            $availability = AppointmentAvailability::where('created_by', creatorId())->findOrFail($id);
            $availability->delete();

            return redirect()->back()->with('success', __('Availability removed successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }
}
