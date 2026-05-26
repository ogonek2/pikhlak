<?php

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
});
