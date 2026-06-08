<?php

use Illuminate\Support\Facades\Route;
use Workdo\Rotas\Http\Controllers\RotaController;
use Workdo\Rotas\Http\Controllers\RotaTemplateController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Rotas'])->group(function () {

    Route::get('rotas', [RotaController::class, 'index'])->name('rotas.index');
    Route::post('rotas', [RotaController::class, 'store'])->name('rotas.store');
    Route::get('rotas/{id}', [RotaController::class, 'show'])->name('rotas.show');
    Route::put('rotas/{id}', [RotaController::class, 'update'])->name('rotas.update');
    Route::delete('rotas/{id}', [RotaController::class, 'destroy'])->name('rotas.destroy');
    Route::post('rotas/{id}/generate', [RotaController::class, 'generate'])->name('rotas.generate');

    Route::get('rota-templates', [RotaTemplateController::class, 'index'])->name('rota-templates.index');
    Route::post('rota-templates', [RotaTemplateController::class, 'store'])->name('rota-templates.store');
    Route::put('rota-templates/{id}', [RotaTemplateController::class, 'update'])->name('rota-templates.update');
    Route::delete('rota-templates/{id}', [RotaTemplateController::class, 'destroy'])->name('rota-templates.destroy');

    Route::prefix('rota-assignments')->group(function () {
        Route::post('assign', [RotaController::class, 'assignEmployee'])->name('rota-assignments.assign');
        Route::post('{id}/bulk-assign', [RotaController::class, 'bulkAssign'])->name('rota-assignments.bulk-assign');
        Route::put('{id}', [RotaController::class, 'updateAssignment'])->name('rota-assignments.update');
        Route::delete('{id}', [RotaController::class, 'deleteAssignment'])->name('rota-assignments.destroy');
    });
});
