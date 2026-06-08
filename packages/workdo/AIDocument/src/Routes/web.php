<?php

use Illuminate\Support\Facades\Route;
use Workdo\AIDocument\Http\Controllers\AIDocumentController;
use Workdo\AIDocument\Http\Controllers\AIDocumentPromptController;
use Workdo\AIDocument\Http\Controllers\AIDocumentSettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:AIDocument'])->group(function () {
    Route::prefix('ai-document')->name('ai-document.')->group(function () {
        Route::get('/settings', [AIDocumentSettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AIDocumentSettingsController::class, 'store'])->name('settings.store');

        Route::get('/prompts', [AIDocumentPromptController::class, 'index'])->name('prompts.index');
        Route::post('/prompts', [AIDocumentPromptController::class, 'store'])->name('prompts.store');
        Route::get('/prompts/{id}', [AIDocumentPromptController::class, 'show'])->name('prompts.show');
        Route::put('/prompts/{id}', [AIDocumentPromptController::class, 'update'])->name('prompts.update');
        Route::delete('/prompts/{id}', [AIDocumentPromptController::class, 'destroy'])->name('prompts.destroy');

        Route::post('/generate', [AIDocumentController::class, 'generate'])->name('generate');
        Route::get('/generations', [AIDocumentController::class, 'generations'])->name('generations');
        Route::get('/generations/{id}', [AIDocumentController::class, 'showGeneration'])->name('generations.show');
    });
});
