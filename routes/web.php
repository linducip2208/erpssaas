<?php



use App\Http\Controllers\MediaController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\BankTransferPaymentController;

use App\Http\Controllers\CouponController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\HelpdeskCategoryController;
use App\Http\Controllers\HelpdeskTicketController;
use App\Http\Controllers\HelpdeskReplyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SalesProposalController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Controllers\RequestQuotationController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceiptNoteController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\AssetDepreciationController;
use App\Http\Controllers\AssetDisposalController;
use Inertia\Inertia;


Route::middleware(['auth', 'verified', 'PlanModuleCheck'])->group(function () {
    // Route::get('/dashboard', function () {
    //     return Inertia::render('dashboard');
    // })->name('dashboard');

    Route::get('dashboard', [HomeController::class, 'Dashboard'])->name('dashboard');

    // Profile management routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resource management routes
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/change-password', [UserController::class, 'changePassword'])->name('users.change-password');
    Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
    Route::post('users/leave-impersonation', [UserController::class, 'leaveImpersonation'])->name('users.leave-impersonation');
    Route::get('users/login/history', [UserController::class, 'loginHistory'])->name('users.login-history');
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('transfers', TransferController::class)->except(['edit', 'update']);



    Route::resource('roles', RoleController::class);

    // purchase invoices
    Route::resource('purchase-invoices', PurchaseInvoiceController::class);
    Route::post('purchase-invoices/{purchaseInvoice}/post', [PurchaseInvoiceController::class, 'post'])->name('purchase-invoices.post');
    Route::get('purchase-invoices/{purchaseInvoice}/print', [PurchaseInvoiceController::class, 'print'])->name('purchase-invoices.print');

    // sales invoices
    Route::resource('sales-invoices', SalesInvoiceController::class);
    Route::post('sales-invoices/{salesInvoice}/post', [SalesInvoiceController::class, 'post'])->name('sales-invoices.post');
    Route::get('sales-invoices/{salesInvoice}/print', [SalesInvoiceController::class, 'print'])->name('sales-invoices.print');
    Route::get('sales-invoices/warehouse/products', [SalesInvoiceController::class, 'getWarehouseProducts'])->name('sales-invoices.warehouse.products');
    Route::get('sales-invoices/services/list', [SalesInvoiceController::class, 'getServices'])->name('sales-invoices.services');

    // purchase returns
    Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');
    Route::get('purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
    Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
    Route::get('purchase-returns/{return}', [PurchaseReturnController::class, 'show'])->name('purchase-returns.show');
    Route::delete('purchase-returns/{return}', [PurchaseReturnController::class, 'destroy'])->name('purchase-returns.destroy');
    Route::post('purchase-returns/{return}/approve', [PurchaseReturnController::class, 'approve'])->name('purchase-returns.approve');
    Route::post('purchase-returns/{return}/complete', [PurchaseReturnController::class, 'complete'])->name('purchase-returns.complete');

    // sales returns
    Route::get('sales-returns', [SalesReturnController::class, 'index'])->name('sales-returns.index');
    Route::get('sales-returns/create', [SalesReturnController::class, 'create'])->name('sales-returns.create');
    Route::post('sales-returns', [SalesReturnController::class, 'store'])->name('sales-returns.store');
    Route::get('sales-returns/{salesReturn}', [SalesReturnController::class, 'show'])->name('sales-returns.show');
    Route::delete('sales-returns/{salesReturn}', [SalesReturnController::class, 'destroy'])->name('sales-returns.destroy');
    Route::post('sales-returns/{salesReturn}/approve', [SalesReturnController::class, 'approve'])->name('sales-returns.approve');
    Route::post('sales-returns/{salesReturn}/complete', [SalesReturnController::class, 'complete'])->name('sales-returns.complete');

    // Helpdesk Routes
    Route::resource('helpdesk-categories', HelpdeskCategoryController::class);
    Route::resource('helpdesk-tickets', HelpdeskTicketController::class);

    // Helpdesk Replies (AJAX endpoints)
    Route::post('helpdesk-tickets/{ticket}/replies', [HelpdeskReplyController::class, 'store'])->name('helpdesk-replies.store');
    Route::delete('helpdesk-replies/{reply}', [HelpdeskReplyController::class, 'destroy'])->name('helpdesk-replies.destroy');
    Route::resource('plans', PlanController::class);
    Route::resource('coupons', CouponController::class);
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::get('plans/{plan}/subscribe', [PlanController::class, 'subscribe'])->name('plans.subscribe');
    Route::post('plans/{plan}/start-trial', [PlanController::class, 'startTrial'])->name('plans.start-trial');
    Route::post('plans/add-on/update-price', [PlanController::class, 'updateModulePrice'])->name('plans.add-on.update-price');
    Route::post('plans/apply-coupon', [PlanController::class, 'applyCoupon'])->name('plans.apply-coupon');
    Route::post('plans/{plan}/assign-free', [PlanController::class, 'assignFreePlan'])->name('plans.assign-free');
    Route::post('subscriptions', [PlanController::class, 'store'])->name('subscriptions.store');

    // Add-on management routes
    Route::get('add-ons', [ModuleController::class, 'index'])->name('add-ons.index');
    Route::get('add-on/upload', [ModuleController::class, 'upload'])->name('add-on.upload');
    Route::post('add-ons/install', [ModuleController::class, 'install'])->name('add-ons.install');
    Route::post('add-on/{name}/enable', [ModuleController::class, 'enable'])->name('add-on.enable');
    Route::get('user/active-modules', [ModuleController::class, 'getUserActiveModules'])->name('user.active-modules');
    Route::delete('user/active-modules/{moduleId}', [ModuleController::class, 'removeUserActiveModule'])->name('user.active-modules.remove');

    // Settings management routes
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/brand', [SettingController::class, 'updateBrandSettings'])->name('settings.brand.update');
    Route::post('settings/company', [SettingController::class, 'updateCompanySettings'])->name('settings.company.update');
    Route::post('settings/system', [SettingController::class, 'updateSystemSettings'])->name('settings.system.update');
    Route::post('settings/currency', [SettingController::class, 'updateCurrencySettings'])->name('settings.currency.update');
    Route::post('settings/cache/clear', [SettingController::class, 'clearCache'])->name('settings.cache.clear');
    Route::post('settings/optimize', [SettingController::class, 'optimizeSite'])->name('settings.optimize');
    Route::post('settings/cookie', [SettingController::class, 'updateCookieSettings'])->name('settings.cookie.update');
    Route::post('settings/seo', [SettingController::class, 'updateSeoSettings'])->name('settings.seo.update');
    Route::post('settings/storage', [SettingController::class, 'updateStorageSettings'])->name('settings.storage.update');
    Route::get('settings/cookie/download', [SettingController::class, 'downloadCookieData'])->name('settings.cookie.download');
    Route::post('settings/email', [SettingController::class, 'updateEmailSettings'])->name('settings.email.update');
    Route::post('settings/email/test', [SettingController::class, 'testEmail'])->name('settings.email.test');
    Route::post('settings/pusher', [SettingController::class, 'updatePusherSettings'])->name('settings.pusher.update');
    Route::post('settings/bank-transfer', [SettingController::class, 'updateBankTransferSettings'])->name('settings.bank-transfer.update');
    Route::post('email-notification-settings-save', [SettingController::class, 'mailNotificationStore'])->name('email.notification.setting.store');

    // Bank Transfer Payment routes (legacy - keep for backward compatibility)
    Route::post('bank-transfer', [BankTransferPaymentController::class, 'store'])->name('payment.bank-transfer.store');
    Route::get('bank-transfer', [BankTransferPaymentController::class, 'index'])->name('bank-transfer.index');
    Route::post('bank-transfer/update/{id}', [BankTransferPaymentController::class, 'update'])->name('bank-transfer.update');
    Route::post('bank-transfer/{payment}/reject', [BankTransferPaymentController::class, 'reject'])->name('bank-transfer.reject');
    Route::delete('bank-transfer/{payment}', [BankTransferPaymentController::class, 'destroy'])->name('bank-transfer.destroy');

    // Dynamic Payment Gateway Management
    Route::resource('gateways', GatewayController::class)->except(['show']);
    Route::post('gateways/{gateway}/toggle', [GatewayController::class, 'toggle'])->name('gateways.toggle');
    Route::get('gateways/keys/format', [GatewayController::class, 'getKeys'])->name('gateways.keys');
    Route::get('gateways/presets/load', [GatewayController::class, 'preset'])->name('gateways.preset');

    // Unified Dynamic Payment Routes
    Route::post('payment/{gateway}/pay', [PaymentController::class, 'pay'])->name('payment.pay');
    Route::get('payment/{gateway}/callback', [PaymentController::class, 'callback'])->name('payment.callback');
    Route::get('payment/{gateway}/status', [PaymentController::class, 'status'])->name('payment.status');
    Route::post('payment/{gateway}/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

    // Language management routes
    Route::get('/languages/manage', [TranslationController::class, 'manage'])->name('languages.manage');
    Route::post('/languages/{locale}/update', [TranslationController::class, 'updateTranslations'])->name('languages.update');
    Route::get('/languages/{locale}/package/{packageName}', [TranslationController::class, 'getPackageTranslations'])->name('languages.package.translations');
    Route::post('/languages/{locale}/package/{packageName}/update', [TranslationController::class, 'updatePackageTranslations'])->name('languages.package.update');
    Route::post('/languages/create', [TranslationController::class, 'createLanguage'])->name('languages.create');
    Route::delete('/languages/{languageCode}', [TranslationController::class, 'deleteLanguage'])->name('languages.delete');
    Route::post('/languages/change', [TranslationController::class, 'changeLanguage'])->name('languages.change');
    Route::patch('/languages/{languageCode}/toggle', [TranslationController::class, 'toggleLanguageStatus'])->name('languages.toggle');

    // Email templates routes
    Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::get('email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
    Route::get('email-templates/{emailTemplate}/language/{lang}', [EmailTemplateController::class, 'getLanguageContent'])->name('email-templates.language-content');
    Route::put('email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::put('email-templates/{emailTemplate}/update-meta', [EmailTemplateController::class, 'updateMeta'])->name('email-templates.update-meta');

    // Notification template routes
    Route::get('notification-templates', [NotificationTemplateController::class, 'index'])->name('notification-templates.index');
    Route::get('notification-templates/{notificationTemplate}/edit', [NotificationTemplateController::class, 'edit'])->name('notification-templates.edit');
    Route::get('notification-templates/{notificationTemplate}/language/{lang}', [NotificationTemplateController::class, 'getLanguageContent'])->name('notification-templates.language-content');
    Route::put('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'update'])->name('notification-templates.update');

     // Proposal Routes
    Route::resource('sales-proposals', SalesProposalController::class);
     Route::get('sales-proposals/{salesProposal}/print', [SalesProposalController::class, 'print'])->name('sales-proposals.print');
     Route::post('sales-proposals/{salesProposal}/sent', [SalesProposalController::class, 'sent'])->name('sales-proposals.sent');
     Route::post('sales-proposals/{salesProposal}/accept', [SalesProposalController::class, 'accept'])->name('sales-proposals.accept');
     Route::post('sales-proposals/{salesProposal}/reject', [SalesProposalController::class, 'reject'])->name('sales-proposals.reject');
     Route::post('sales-proposals/{salesProposal}/convert-to-invoice', [SalesProposalController::class, 'convertToInvoice'])->name('sales-proposals.convert-to-invoice');
     Route::get('sales-proposals/warehouse/products', [SalesProposalController::class, 'getWarehouseProducts'])->name('sales-proposals.warehouse.products');

     // Sales Orders
     Route::resource('sales-orders', SalesOrderController::class);
     Route::post('sales-orders/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
     Route::post('sales-orders/{order}/generate-delivery', [SalesOrderController::class, 'generateDelivery'])->name('sales-orders.generate-delivery');
     Route::get('sales-orders/warehouse/products', [SalesOrderController::class, 'getProducts'])->name('sales-orders.warehouse.products');

     // Delivery Orders
     Route::resource('delivery-orders', DeliveryOrderController::class);
     Route::post('delivery-orders/{order}/ship', [DeliveryOrderController::class, 'ship'])->name('delivery-orders.ship');
     Route::post('delivery-orders/{order}/deliver', [DeliveryOrderController::class, 'deliver'])->name('delivery-orders.deliver');

    // Purchase Requisitions
    Route::resource('purchase-requisitions', PurchaseRequisitionController::class);
    Route::post('purchase-requisitions/{requisition}/submit', [PurchaseRequisitionController::class, 'submit'])->name('purchase-requisitions.submit');
    Route::post('purchase-requisitions/{requisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('purchase-requisitions.approve');
    Route::post('purchase-requisitions/{requisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('purchase-requisitions.reject');
    Route::post('purchase-requisitions/{requisition}/convert-to-po', [PurchaseRequisitionController::class, 'convertToPO'])->name('purchase-requisitions.convert-to-po');

    // Request Quotations
    Route::resource('request-quotations', RequestQuotationController::class);
    Route::post('request-quotations/{quotation}/record-response', [RequestQuotationController::class, 'recordResponse'])->name('request-quotations.record-response');
    Route::get('request-quotations/{quotation}/compare', [RequestQuotationController::class, 'compare'])->name('request-quotations.compare');
    Route::post('request-quotations/{quotation}/award/{vendor}', [RequestQuotationController::class, 'award'])->name('request-quotations.award');

    // Purchase Orders
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('purchase-orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{order}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::get('purchase-orders/{order}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
    Route::get('purchase-orders/warehouse/products', [PurchaseOrderController::class, 'getProducts'])->name('purchase-orders.warehouse.products');

    // Goods Receipt Notes
    Route::resource('goods-receipt-notes', GoodsReceiptNoteController::class)->except(['edit','update']);
    Route::post('goods-receipt-notes/{grn}/inspect', [GoodsReceiptNoteController::class, 'inspect'])->name('goods-receipt-notes.inspect');
    Route::get('goods-receipt-notes/{grn}/print', [GoodsReceiptNoteController::class, 'print'])->name('goods-receipt-notes.print');

    // Messenger routes
    Route::get('messenger', [MessengerController::class, 'index'])->name('messenger.index');
    Route::post('messenger/send', [MessengerController::class, 'send'])->name('messenger.send');
    Route::get('messenger/contacts', [MessengerController::class, 'getContacts'])->name('messenger.contacts');
    Route::get('messenger/messages/{userId}', [MessengerController::class, 'getMessages'])->name('messenger.messages');
    Route::post('messenger/toggle-favorite', [MessengerController::class, 'toggleFavorite'])->name('messenger.toggle-favorite');
    Route::get('messenger/favorites', [MessengerController::class, 'getFavorites'])->name('messenger.favorites');
    Route::put('messenger/messages/{messageId}/edit', [MessengerController::class, 'editMessage'])->name('messenger.edit-message');
    Route::delete('messenger/messages/{messageId}', [MessengerController::class, 'deleteMessage'])->name('messenger.delete-message');
    Route::post('/messenger/set-offline', [MessengerController::class, 'setOffline'])->name('messenger.set-offline');
    Route::post('/messenger/update-presence', [MessengerController::class, 'updatePresence'])->name('messenger.update-presence');
    Route::get('/messenger/online-users', [MessengerController::class, 'getOnlineUsers'])->name('messenger.online-users');
    Route::post('/messenger/toggle-pin', [MessengerController::class, 'togglePin'])->name('messenger.toggle-pin');
    Route::get('/messenger/pinned', [MessengerController::class, 'getPinned'])->name('messenger.pinned');
    Route::get('/messenger/check-new-messages', [MessengerController::class, 'checkNewMessages'])->name('messenger.check-new-messages');

    // Media Library API routes
    Route::get('media-library', [MediaController::class, 'page'])->name('media-library');
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media/batch', [MediaController::class, 'batchStore'])->name('media.batch');
    Route::get('media/{id}/download', [MediaController::class, 'download'])->name('media.download');
    Route::delete('media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::post('media/directories', [MediaController::class, 'createDirectory'])->name('media.directories.create');
    Route::patch('media/{id}/directory', [MediaController::class, 'updateMediaDirectory'])->name('media.directory.update');

    // Fixed Asset Management
    Route::resource('asset-categories', AssetCategoryController::class);
    Route::resource('fixed-assets', FixedAssetController::class);
    Route::post('fixed-assets/{asset}/run-depreciation', [FixedAssetController::class, 'runDepreciation'])->name('fixed-assets.run-depreciation');
    Route::post('fixed-assets/run-all-depreciation', [FixedAssetController::class, 'runAllDepreciation'])->name('fixed-assets.run-all-depreciation');
    Route::post('fixed-assets/{asset}/dispose', [FixedAssetController::class, 'dispose'])->name('fixed-assets.dispose');
    Route::resource('asset-depreciations', AssetDepreciationController::class)->only(['index', 'show']);
    Route::resource('asset-disposals', AssetDisposalController::class)->only(['index', 'create', 'store', 'show']);
});

Route::get('/translations/{locale}', [TranslationController::class, 'getTranslations'])->name('languages.translations');
Route::post('/cookie-consent-log', [SettingController::class, 'logCookieConsent'])->name('cookie.consent.log');

require __DIR__.'/updater.php';
require __DIR__.'/auth.php';
