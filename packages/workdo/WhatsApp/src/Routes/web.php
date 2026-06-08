<?php

use Illuminate\Support\Facades\Route;
use Workdo\WhatsApp\Http\Controllers\WhatsAppController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:WhatsApp'])->group(function () {
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/dashboard', [WhatsAppController::class, 'index'])->name('dashboard');
        Route::get('/templates', [WhatsAppController::class, 'templates'])->name('templates.index');
        Route::post('/templates', [WhatsAppController::class, 'templateStore'])->name('templates.store');
        Route::put('/templates/{id}', [WhatsAppController::class, 'templateUpdate'])->name('templates.update');
        Route::delete('/templates/{id}', [WhatsAppController::class, 'templateDestroy'])->name('templates.destroy');
        Route::post('/send', [WhatsAppController::class, 'send'])->name('send');
        Route::get('/logs', [WhatsAppController::class, 'logs'])->name('logs.index');
        Route::get('/settings', [WhatsAppController::class, 'settings'])->name('settings.index');
        Route::post('/settings', [WhatsAppController::class, 'settings'])->name('settings.store');
    });
});
