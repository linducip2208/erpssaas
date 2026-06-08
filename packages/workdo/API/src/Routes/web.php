<?php

use Workdo\API\Http\Controllers\ApiTokenController;
use Workdo\API\Http\Controllers\ApiLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:API'])->group(function () {
    Route::prefix('api/tokens')->name('api.tokens.')->group(function () {
        Route::get('/', [ApiTokenController::class, 'index'])->name('index');
        Route::post('/', [ApiTokenController::class, 'store'])->name('store');
        Route::put('/{token}', [ApiTokenController::class, 'update'])->name('update');
        Route::delete('/{token}', [ApiTokenController::class, 'destroy'])->name('destroy');
        Route::post('/{token}/regenerate', [ApiTokenController::class, 'regenerate'])->name('regenerate');
        Route::post('/{token}/revoke', [ApiTokenController::class, 'revoke'])->name('revoke');
    });

    Route::prefix('api/logs')->name('api.logs.')->group(function () {
        Route::get('/', [ApiLogController::class, 'index'])->name('index');
    });
});
