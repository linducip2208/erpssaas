<?php

use Illuminate\Support\Facades\Route;
use Workdo\Appointment\Http\Controllers\AppointmentController;
use Workdo\Appointment\Http\Controllers\AppointmentTypeController;
use Workdo\Appointment\Http\Controllers\AppointmentAvailabilityController;

Route::get('book-appointment', [AppointmentController::class, 'publicBooking'])
    ->withoutMiddleware(['auth', 'verified', 'PlanModuleCheck:Appointment'])
    ->name('appointments.public-booking');

Route::post('appointments/public', [AppointmentController::class, 'store'])
    ->withoutMiddleware(['auth', 'verified', 'PlanModuleCheck:Appointment'])
    ->name('appointments.public-store');

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Appointment'])->group(function () {

    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('appointments/{id}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('appointments/{id}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');

    Route::get('appointment-types', [AppointmentTypeController::class, 'index'])->name('appointment-types.index');
    Route::post('appointment-types', [AppointmentTypeController::class, 'store'])->name('appointment-types.store');
    Route::put('appointment-types/{id}', [AppointmentTypeController::class, 'update'])->name('appointment-types.update');
    Route::delete('appointment-types/{id}', [AppointmentTypeController::class, 'destroy'])->name('appointment-types.destroy');

    Route::get('appointment-availability', [AppointmentAvailabilityController::class, 'index'])->name('appointment-availability.index');
    Route::post('appointment-availability', [AppointmentAvailabilityController::class, 'store'])->name('appointment-availability.store');
    Route::put('appointment-availability/{id}', [AppointmentAvailabilityController::class, 'update'])->name('appointment-availability.update');
    Route::delete('appointment-availability/{id}', [AppointmentAvailabilityController::class, 'destroy'])->name('appointment-availability.destroy');
});
