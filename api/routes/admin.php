<?php

use App\Http\Controllers\Admin\ClientPortal\ClientBotController;
use App\Http\Controllers\Admin\ClientPortal\ClientBotNotificationController;
use App\Http\Controllers\Admin\ClientPortal\ClientManagerRequestController;
use App\Http\Controllers\Admin\ClientPortal\CrmSyncController;
use App\Http\Controllers\Admin\ClientPortal\ClientDashboardController;
use App\Http\Controllers\Admin\ClientPortal\RentBuyoutCalculatorController;
use App\Http\Controllers\Admin\ClientPortal\RentalClientApiController;
use App\Http\Controllers\Admin\ClientPortal\RentalClientController;
use App\Http\Controllers\Admin\ClientPortal\RentalClientNestedController;
use App\Http\Controllers\Admin\ClientPortal\TrafficChannelController;
use App\Http\Controllers\Admin\AiControlController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BotController;
use App\Http\Controllers\Admin\BotMessageController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ChatSettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\LeadStatusController;
use App\Http\Controllers\Admin\ReferralLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::middleware(['auth', 'admin.project'])->group(function (): void {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('bot', [BotController::class, 'show'])->name('bot.show');
    Route::put('bot', [BotController::class, 'update'])->name('bot.update');
    Route::get('bot/messages', [BotMessageController::class, 'edit'])->name('bot.messages');
    Route::put('bot/messages', [BotMessageController::class, 'update'])->name('bot.messages.update');

    Route::post('cars/{car}/duplicate', [CarController::class, 'duplicate'])->name('cars.duplicate');
    Route::post('cars/{car}/generate-ai', [CarController::class, 'generateAi'])->name('cars.generate-ai');
    Route::resource('cars', CarController::class);

    Route::prefix('ai')->name('ai.')->group(function (): void {
        Route::get('/', [AiControlController::class, 'index'])->name('index');
        Route::get('models', [AiControlController::class, 'models'])->name('models');
        Route::post('models', [AiControlController::class, 'storeModel'])->name('models.store');
        Route::put('models/{model}', [AiControlController::class, 'updateModel'])->name('models.update');
        Route::get('routes', [AiControlController::class, 'routes'])->name('routes');
        Route::get('routes/create', [AiControlController::class, 'createRoute'])->name('routes.create');
        Route::post('routes', [AiControlController::class, 'storeRoute'])->name('routes.store');
        Route::get('routes/{route}/edit', [AiControlController::class, 'editRoute'])->name('routes.edit');
        Route::put('routes/{route}', [AiControlController::class, 'updateRoute'])->name('routes.update');
        Route::delete('routes/{route}', [AiControlController::class, 'destroyRoute'])->name('routes.destroy');
        Route::get('settings', [AiControlController::class, 'settings'])->name('settings');
        Route::put('settings', [AiControlController::class, 'updateSettings'])->name('settings.update');
        Route::get('prompts', [AiControlController::class, 'prompts'])->name('prompts');
        Route::post('prompts', [AiControlController::class, 'storePrompt'])->name('prompts.store');
        Route::post('prompts/{prompt}/publish', [AiControlController::class, 'publishPrompt'])->name('prompts.publish');
        Route::get('rules', [AiControlController::class, 'rules'])->name('rules');
        Route::post('rules', [AiControlController::class, 'storeRule'])->name('rules.store');
        Route::post('rules/preset/{preset}', [AiControlController::class, 'storeRulePreset'])->name('rules.preset');
        Route::put('rules/{rule}', [AiControlController::class, 'updateRule'])->name('rules.update');
        Route::delete('rules/{rule}', [AiControlController::class, 'destroyRule'])->name('rules.destroy');
        Route::post('routes/preset/{preset}', [AiControlController::class, 'storeRoutePreset'])->name('routes.preset');
        Route::get('topics', [AiControlController::class, 'topics'])->name('topics');
        Route::post('topics/allowed', [AiControlController::class, 'storeAllowedTopic'])->name('topics.allowed.store');
        Route::post('topics/forbidden', [AiControlController::class, 'storeForbiddenTopic'])->name('topics.forbidden.store');
        Route::delete('topics/allowed/{topic}', [AiControlController::class, 'destroyAllowedTopic'])->name('topics.allowed.destroy');
        Route::delete('topics/forbidden/{topic}', [AiControlController::class, 'destroyForbiddenTopic'])->name('topics.forbidden.destroy');
        Route::get('templates', [AiControlController::class, 'templates'])->name('templates');
        Route::post('templates', [AiControlController::class, 'storeTemplate'])->name('templates.store');
        Route::get('warming', [AiControlController::class, 'warming'])->name('warming');
        Route::put('warming', [AiControlController::class, 'updateWarming'])->name('warming.update');
        Route::get('filters', [AiControlController::class, 'filters'])->name('filters');
        Route::put('filters', [AiControlController::class, 'updateFilters'])->name('filters.update');
        Route::get('playground', [AiControlController::class, 'playground'])->name('playground');
        Route::post('playground', [AiControlController::class, 'runPlayground'])->name('playground.run');
    });

    Route::resource('faq', FaqController::class)->except(['show']);

    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::patch('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::get('lead-statuses', [LeadStatusController::class, 'index'])->name('lead-statuses.index');
    Route::patch('lead-statuses/{status}', [LeadStatusController::class, 'update'])->name('lead-statuses.update');

    Route::get('referrals', [ReferralLinkController::class, 'index'])->name('referrals.index');
    Route::get('referrals/create', [ReferralLinkController::class, 'create'])->name('referrals.create');
    Route::post('referrals', [ReferralLinkController::class, 'store'])->name('referrals.store');
    Route::get('referrals/{referralLink}', [ReferralLinkController::class, 'show'])->name('referrals.show');
    Route::get('referrals/{referralLink}/edit', [ReferralLinkController::class, 'edit'])->name('referrals.edit');
    Route::put('referrals/{referralLink}', [ReferralLinkController::class, 'update'])->name('referrals.update');
    Route::delete('referrals/{referralLink}', [ReferralLinkController::class, 'destroy'])->name('referrals.destroy');

    Route::get('chats/settings', [ChatSettingsController::class, 'edit'])->name('chats.settings');
    Route::put('chats/settings', [ChatSettingsController::class, 'update'])->name('chats.settings.update');
    Route::get('chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('chats/{chat}/message', [ChatController::class, 'sendMessage'])->name('chats.message');
    Route::post('chats/{chat}/mode', [ChatController::class, 'setMode'])->name('chats.mode');
    Route::post('chats/{chat}/operator-ack', [ChatController::class, 'acknowledgeOperator'])->name('chats.operator-ack');

    Route::prefix('client')->name('client.')->group(function (): void {
        Route::get('/', [ClientDashboardController::class, 'index'])->name('dashboard');
        Route::get('bot', [ClientBotController::class, 'show'])->name('bot.show');
        Route::put('bot', [ClientBotController::class, 'update'])->name('bot.update');
        Route::put('bot/notifications', [ClientBotNotificationController::class, 'update'])->name('bot.notifications.update');
        Route::get('bot/manager-requests', [ClientManagerRequestController::class, 'index'])->name('bot.manager-requests');
        Route::put('bot/manager-settings', [ClientManagerRequestController::class, 'updateSettings'])->name('bot.manager-settings');
        Route::post('bot/manager-requests/{managerRequest}/in-progress', [ClientManagerRequestController::class, 'inProgress'])->name('bot.manager-requests.in-progress');
        Route::post('bot/manager-requests/{managerRequest}/resolve', [ClientManagerRequestController::class, 'resolve'])->name('bot.manager-requests.resolve');
        Route::post('bot/manager-requests/{managerRequest}/cancel', [ClientManagerRequestController::class, 'cancel'])->name('bot.manager-requests.cancel');
        Route::post('crm/sync', [CrmSyncController::class, 'store'])->name('crm.sync');
        Route::get('traffic', [TrafficChannelController::class, 'index'])->name('traffic.index');
        Route::post('traffic/sync-all', [TrafficChannelController::class, 'syncAll'])->name('traffic.sync-all');
        Route::get('traffic/{channel}', [TrafficChannelController::class, 'show'])->name('traffic.show');
        Route::put('traffic/{channel}/credentials', [TrafficChannelController::class, 'updateCredentials'])->name('traffic.credentials');
        Route::post('traffic/{channel}/sync', [TrafficChannelController::class, 'sync'])->name('traffic.sync');
        Route::get('clients', [RentalClientController::class, 'index'])->name('clients.index');
        Route::get('clients/create', [RentalClientController::class, 'create'])->name('clients.create');
        Route::post('clients/calculator-preview', [RentBuyoutCalculatorController::class, 'preview'])->name('clients.calculator-preview');
        Route::post('clients', [RentalClientController::class, 'store'])->name('clients.store');
        Route::get('clients/{client}', [RentalClientController::class, 'show'])->name('clients.show');
        Route::get('clients/{client}/edit', [RentalClientController::class, 'edit'])->name('clients.edit');
        Route::put('clients/{client}', [RentalClientController::class, 'update'])->name('clients.update');
        Route::post('clients/{client}/claim-telegram', [RentalClientController::class, 'claimTelegram'])->name('clients.claim-telegram');
        Route::get('clients/{client}/profile-data', [RentalClientApiController::class, 'show'])->name('clients.profile-data');

        Route::post('clients/{client}/phones', [RentalClientNestedController::class, 'storePhone'])->name('clients.phones.store');
        Route::delete('clients/{client}/phones/{phone}', [RentalClientNestedController::class, 'destroyPhone'])->name('clients.phones.destroy');
        Route::post('clients/{client}/vehicles', [RentalClientNestedController::class, 'storeVehicle'])->name('clients.vehicles.store');
        Route::put('clients/{client}/vehicles/{vehicle}', [RentalClientNestedController::class, 'updateVehicle'])->name('clients.vehicles.update');
        Route::delete('clients/{client}/vehicles/{vehicle}', [RentalClientNestedController::class, 'destroyVehicle'])->name('clients.vehicles.destroy');
        Route::post('clients/{client}/contracts', [RentalClientNestedController::class, 'storeContract'])->name('clients.contracts.store');
        Route::put('clients/{client}/contracts/{contract}', [RentalClientNestedController::class, 'updateContract'])->name('clients.contracts.update');
        Route::delete('clients/{client}/contracts/{contract}', [RentalClientNestedController::class, 'destroyContract'])->name('clients.contracts.destroy');
        Route::post('clients/{client}/payments', [RentalClientNestedController::class, 'storePayment'])->name('clients.payments.store');
        Route::put('clients/{client}/payments/{payment}', [RentalClientNestedController::class, 'updatePayment'])->name('clients.payments.update');
        Route::delete('clients/{client}/payments/{payment}', [RentalClientNestedController::class, 'destroyPayment'])->name('clients.payments.destroy');
        Route::post('clients/{client}/payments/{payment}/paid', [RentalClientNestedController::class, 'markPaymentPaid'])->name('clients.payments.paid');
        Route::post('clients/{client}/insurances', [RentalClientNestedController::class, 'storeInsurance'])->name('clients.insurances.store');
        Route::put('clients/{client}/insurances/{insurance}', [RentalClientNestedController::class, 'updateInsurance'])->name('clients.insurances.update');
        Route::delete('clients/{client}/insurances/{insurance}', [RentalClientNestedController::class, 'destroyInsurance'])->name('clients.insurances.destroy');
        Route::post('clients/{client}/maintenances', [RentalClientNestedController::class, 'storeMaintenance'])->name('clients.maintenances.store');
        Route::put('clients/{client}/maintenances/{maintenance}', [RentalClientNestedController::class, 'updateMaintenance'])->name('clients.maintenances.update');
        Route::delete('clients/{client}/maintenances/{maintenance}', [RentalClientNestedController::class, 'destroyMaintenance'])->name('clients.maintenances.destroy');
    });
});
