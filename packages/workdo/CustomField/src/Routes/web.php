<?php

use Workdo\CustomField\Http\Controllers\CustomFieldGroupController;
use Workdo\CustomField\Http\Controllers\CustomFieldController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:CustomField'])->group(function () {
    Route::prefix('custom-field/groups')->name('custom-field.groups.')->group(function () {
        Route::get('/', [CustomFieldGroupController::class, 'index'])->name('index');
        Route::post('/', [CustomFieldGroupController::class, 'store'])->name('store');
        Route::put('/{group}', [CustomFieldGroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [CustomFieldGroupController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('custom-field/fields')->name('custom-field.fields.')->group(function () {
        Route::get('/', [CustomFieldController::class, 'index'])->name('index');
        Route::post('/', [CustomFieldController::class, 'store'])->name('store');
        Route::put('/{field}', [CustomFieldController::class, 'update'])->name('update');
        Route::delete('/{field}', [CustomFieldController::class, 'destroy'])->name('destroy');
    });
});
