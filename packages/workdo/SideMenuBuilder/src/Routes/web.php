<?php

use Workdo\SideMenuBuilder\Http\Controllers\CustomMenuController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:SideMenuBuilder'])->group(function () {
    Route::prefix('side-menu-builder/menus')->name('side-menu-builder.menus.')->group(function () {
        Route::get('/', [CustomMenuController::class, 'index'])->name('index');
        Route::post('/', [CustomMenuController::class, 'store'])->name('store');
        Route::put('/{menu}', [CustomMenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [CustomMenuController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [CustomMenuController::class, 'reorder'])->name('reorder');
    });
});
