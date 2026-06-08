<?php

use Illuminate\Support\Facades\Route;
use Workdo\FileSharing\Http\Controllers\SharedFolderController;
use Workdo\FileSharing\Http\Controllers\SharedFileController;
use Workdo\FileSharing\Http\Controllers\FolderPermissionController;
use Workdo\FileSharing\Http\Controllers\SharedLinkController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:FileSharing'])->group(function () {

    Route::get('file-sharing', [SharedFolderController::class, 'index'])->name('file-sharing.index');
    Route::get('file-sharing/{id}', [SharedFolderController::class, 'show'])->name('file-sharing.show');

    Route::prefix('shared-folders')->group(function () {
        Route::post('/', [SharedFolderController::class, 'store'])->name('shared-folders.store');
        Route::put('/{id}', [SharedFolderController::class, 'update'])->name('shared-folders.update');
        Route::delete('/{id}', [SharedFolderController::class, 'destroy'])->name('shared-folders.destroy');
    });

    Route::prefix('shared-files')->group(function () {
        Route::post('/', [SharedFileController::class, 'store'])->name('shared-files.store');
        Route::delete('/{id}', [SharedFileController::class, 'destroy'])->name('shared-files.destroy');
        Route::get('download/{id}', [SharedFileController::class, 'download'])->name('shared-files.download');
    });

    Route::get('shared-files/link/{token}', [SharedFileController::class, 'downloadViaToken'])
        ->withoutMiddleware(['auth', 'verified', 'PlanModuleCheck:FileSharing'])
        ->name('shared-files.download-link');

    Route::prefix('folder-permissions')->group(function () {
        Route::post('/', [FolderPermissionController::class, 'store'])->name('folder-permissions.store');
        Route::delete('/{id}', [FolderPermissionController::class, 'destroy'])->name('folder-permissions.destroy');
        Route::get('users/{folderId}', [FolderPermissionController::class, 'users'])->name('folder-permissions.users');
    });

    Route::prefix('shared-links')->group(function () {
        Route::post('/', [SharedLinkController::class, 'store'])->name('shared-links.store');
        Route::delete('/{id}', [SharedLinkController::class, 'destroy'])->name('shared-links.destroy');
        Route::patch('{id}/toggle', [SharedLinkController::class, 'toggle'])->name('shared-links.toggle');
    });
});
