<?php

use Illuminate\Support\Facades\Route;
use Workdo\Workflow\Http\Controllers\WorkflowController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Workflow'])->group(function () {
    Route::prefix('workflow')->name('workflow.')->group(function () {
        Route::get('/', [WorkflowController::class, 'index'])->name('index');
        Route::post('/', [WorkflowController::class, 'store'])->name('store');
        Route::get('/{id}', [WorkflowController::class, 'show'])->name('show');
        Route::put('/{id}', [WorkflowController::class, 'update'])->name('update');
        Route::delete('/{id}', [WorkflowController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/toggle', [WorkflowController::class, 'toggle'])->name('toggle');
        Route::post('/{id}/execute', [WorkflowController::class, 'execute'])->name('execute');
        Route::get('/logs', [WorkflowController::class, 'logs'])->name('logs.index');
    });
});
