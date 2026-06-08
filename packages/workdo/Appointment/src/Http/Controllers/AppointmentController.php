<?php

namespace Workdo\Appointment\Http\Controllers;

use Workdo\Appointment\Models\Appointment;
use Workdo\Appointment\Models\AppointmentType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-appointments')) {
            $appointments = Appointment::where('created_by', creatorId())
                ->with('type')
                ->when(request('start'), fn($q) => $q->where('start_datetime', '>=', request('start')))
                ->when(request('end'), fn($q) => $q->where('end_datetime', '<=', request('end')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->latest()
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $types = AppointmentType::where('created_by', creatorId())
                ->where('is_active', true)
                ->select('id', 'name', 'color', 'duration_minutes')
                ->get();

            return Inertia::render('Appointment/Index', [
                'appointments' => $appointments,
                'types' => $types,
                'auth' => Auth::user(),
            ]);
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function store(Request $request)
    {
        if ($request->is_public ?? false) {
            $request->validate([
                'title' => 'required|string|max:255',
                'type_id' => 'nullable|exists:appointment_types,id',
                'start_datetime' => 'required|date',
                'attendee_name' => 'required|string|max:255',
                'attendee_email' => 'required|email',
                'attendee_phone' => 'nullable|string|max:20',
                'notes' => 'nullable|string',
            ]);

            $type = AppointmentType::find($request->type_id);
            $duration = $type ? $type->duration_minutes : 30;
            $endDatetime = \Carbon\Carbon::parse($request->start_datetime)->addMinutes($duration);

            Appointment::create([
                'title' => $request->title,
                'type_id' => $request->type_id,
                'start_datetime' => $request->start_datetime,
                'end_datetime' => $endDatetime,
                'attendee_name' => $request->attendee_name,
                'attendee_email' => $request->attendee_email,
                'attendee_phone' => $request->attendee_phone,
                'status' => 'pending',
                'notes' => $request->notes,
                'created_by' => creatorId(),
            ]);

            return redirect()->back()->with('success', __('Appointment booked successfully.'));
        }

        if (Auth::user()->can('create-appointments')) {
            $request->validate([
                'title' => 'required|string|max:255',
                'type_id' => 'nullable|exists:appointment_types,id',
                'start_datetime' => 'required|date',
                'end_datetime' => 'required|date|after:start_datetime',
                'attendee_name' => 'required|string|max:255',
                'attendee_email' => 'required|email',
                'attendee_phone' => 'nullable|string|max:20',
                'notes' => 'nullable|string',
            ]);

            Appointment::create([
                'title' => $request->title,
                'type_id' => $request->type_id,
                'start_datetime' => $request->start_datetime,
                'end_datetime' => $request->end_datetime,
                'attendee_name' => $request->attendee_name,
                'attendee_email' => $request->attendee_email,
                'attendee_phone' => $request->attendee_phone,
                'status' => 'pending',
                'notes' => $request->notes,
                'created_by' => creatorId(),
            ]);

            return redirect()->back()->with('success', __('Appointment created successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->can('edit-appointments')) {
            $appointment = Appointment::where('created_by', creatorId())->findOrFail($id);

            $request->validate([
                'title' => 'required|string|max:255',
                'type_id' => 'nullable|exists:appointment_types,id',
                'start_datetime' => 'required|date',
                'end_datetime' => 'required|date|after:start_datetime',
                'attendee_name' => 'required|string|max:255',
                'attendee_email' => 'required|email',
                'attendee_phone' => 'nullable|string|max:20',
                'status' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $appointment->update($request->only([
                'title', 'type_id', 'start_datetime', 'end_datetime',
                'attendee_name', 'attendee_email', 'attendee_phone',
                'status', 'notes',
            ]));

            return redirect()->back()->with('success', __('Appointment updated successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete-appointments')) {
            $appointment = Appointment::where('created_by', creatorId())->findOrFail($id);
            $appointment->delete();

            return redirect()->back()->with('success', __('Appointment deleted successfully.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function calendar()
    {
        if (Auth::user()->can('view-appointments')) {
            $appointments = Appointment::where('created_by', creatorId())
                ->with('type')
                ->when(request('start'), fn($q) => $q->where('start_datetime', '>=', request('start')))
                ->when(request('end'), fn($q) => $q->where('end_datetime', '<=', request('end')))
                ->get()
                ->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'title' => $appointment->title,
                        'start' => $appointment->start_datetime->toIso8601String(),
                        'end' => $appointment->end_datetime->toIso8601String(),
                        'color' => $appointment->type->color ?? '#6366f1',
                        'status' => $appointment->status,
                        'attendee_name' => $appointment->attendee_name,
                    ];
                });

            return response()->json($appointments);
        }

        return response()->json([], 403);
    }

    public function publicBooking(Request $request)
    {
        $types = AppointmentType::where('created_by', function ($q) use ($request) {
            $q->select('users.created_by')
                ->from('users')
                ->where('users.id', $request->user_id ?? creatorId())
                ->limit(1);
        })->where('is_active', true)->get();

        return Inertia::render('Appointment/PublicBooking', [
            'types' => $types,
        ]);
    }
}
