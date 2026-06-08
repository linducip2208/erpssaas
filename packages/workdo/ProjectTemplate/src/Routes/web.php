<?php

use Illuminate\Support\Facades\Route;
use Workdo\ProjectTemplate\Http\Controllers\ProjectTemplateController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:ProjectTemplate'])->group(function () {
    Route::prefix('project-template')->name('project-template.')->group(function () {
        Route::get('/', [ProjectTemplateController::class, 'index'])->name('index');
        Route::get('/{id}', [ProjectTemplateController::class, 'show'])->name('show');
        Route::post('/', [ProjectTemplateController::class, 'store'])->name('store');
        Route::put('/{id}', [ProjectTemplateController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProjectTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/duplicate', [ProjectTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/{id}/create-project', [ProjectTemplateController::class, 'createProject'])->name('create-project');
    });
});
