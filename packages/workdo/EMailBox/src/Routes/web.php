<?php

use Illuminate\Support\Facades\Route;
use Workdo\EMailBox\Http\Controllers\MailboxController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:EMailBox'])->group(function () {
    Route::prefix('emailbox')->name('emailbox.')->group(function () {
        Route::get('/', [MailboxController::class, 'index'])->name('index');
        Route::post('/mailbox', [MailboxController::class, 'store'])->name('mailbox.store');
        Route::put('/mailbox/{id}', [MailboxController::class, 'update'])->name('mailbox.update');
        Route::delete('/mailbox/{id}', [MailboxController::class, 'destroy'])->name('mailbox.destroy');
        Route::post('/mailbox/{id}/fetch', [MailboxController::class, 'fetchEmails'])->name('fetch');
        Route::post('/send', [MailboxController::class, 'sendEmail'])->name('send');
        Route::put('/email/{id}/read', [MailboxController::class, 'markAsRead'])->name('email.read');
        Route::put('/email/{id}/star', [MailboxController::class, 'toggleStar'])->name('email.star');
        Route::post('/email/{id}/move', [MailboxController::class, 'moveToFolder'])->name('email.move');
        Route::delete('/email/{id}', [MailboxController::class, 'deleteEmail'])->name('email.destroy');
    });
});
