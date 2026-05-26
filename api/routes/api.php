<?php

use App\Http\Controllers\Api\V1\Admin\AiController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\CalculatorController;
use App\Http\Controllers\Api\V1\Admin\CarController;
use App\Http\Controllers\Api\V1\Admin\ChatController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\LeadController;
use App\Http\Controllers\Api\V1\Admin\LeadStatusController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\ReferralController;
use App\Http\Controllers\Api\V1\Admin\SettingController;
use App\Http\Controllers\Api\V1\Bot\BotHealthController;
use App\Http\Controllers\Api\V1\Bot\BotStateController;
use App\Http\Controllers\Api\V1\Bot\BotUpdateController;
use App\Http\Controllers\Api\V1\Webhooks\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')->group(function (): void {
    Route::post('telegram/{botUuid}', [TelegramWebhookController::class, 'handle']);
});

Route::prefix('bot')->middleware('bot.hmac')->group(function (): void {
    Route::post('updates', [BotUpdateController::class, 'store']);
    Route::post('updates/{updateLogId}/ack', [BotUpdateController::class, 'ack']);
    Route::get('chats/{telegramChatId}/state', [BotStateController::class, 'show']);
    Route::get('health-config', [BotHealthController::class, 'config']);
});

Route::prefix('admin')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    Route::middleware(['auth.jwt', 'project.header'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('dashboard/overview', [DashboardController::class, 'overview']);

        Route::get('chats', [ChatController::class, 'index']);
        Route::get('chats/{chatId}', [ChatController::class, 'show']);
        Route::get('chats/{chatId}/messages', [ChatController::class, 'messages']);
        Route::post('chats/{chatId}/reply', [ChatController::class, 'reply']);

        Route::get('leads', [LeadController::class, 'index']);
        Route::post('leads', [LeadController::class, 'store']);
        Route::get('leads/{leadId}', [LeadController::class, 'show']);
        Route::patch('leads/{leadId}', [LeadController::class, 'update']);
        Route::post('leads/{leadId}/notes', [LeadController::class, 'storeNote']);

        Route::get('lead-statuses', [LeadStatusController::class, 'index']);

        Route::get('cars', [CarController::class, 'index']);
        Route::post('cars', [CarController::class, 'store']);
        Route::get('cars/{carId}', [CarController::class, 'show']);
        Route::patch('cars/{carId}', [CarController::class, 'update']);
        Route::delete('cars/{carId}', [CarController::class, 'destroy']);
        Route::post('cars/import', [CarController::class, 'import']);

        Route::get('calculator/profiles', [CalculatorController::class, 'profiles']);
        Route::post('calculator/profiles', [CalculatorController::class, 'storeProfile']);
        Route::post('calculator/simulate', [CalculatorController::class, 'simulate']);

        Route::get('ai/profiles', [AiController::class, 'profiles']);
        Route::post('ai/profiles', [AiController::class, 'storeProfile']);
        Route::get('ai/profiles/{profileId}/prompts', [AiController::class, 'prompts']);
        Route::post('ai/profiles/{profileId}/prompts', [AiController::class, 'storePrompt']);
        Route::post('ai/profiles/{profileId}/prompts/{version}/publish', [AiController::class, 'publishPrompt']);
        Route::get('ai/faq', [AiController::class, 'faqIndex']);
        Route::post('ai/faq', [AiController::class, 'faqStore']);
        Route::post('ai/playground', [AiController::class, 'playground']);

        Route::get('analytics/dialogs', [AnalyticsController::class, 'dialogs']);
        Route::get('analytics/ai-effectiveness', [AnalyticsController::class, 'aiEffectiveness']);
        Route::get('analytics/hot-leads', [AnalyticsController::class, 'hotLeads']);

        Route::get('referrals/campaigns', [ReferralController::class, 'campaigns']);
        Route::post('referrals/campaigns', [ReferralController::class, 'storeCampaign']);
        Route::get('referrals/links', [ReferralController::class, 'links']);
        Route::post('referrals/links', [ReferralController::class, 'storeLink']);

        Route::get('settings', [SettingController::class, 'index']);
        Route::patch('settings', [SettingController::class, 'update']);
        Route::get('bots', [SettingController::class, 'bots']);

        Route::post('media/presign', [MediaController::class, 'presign']);

        Route::get('audit-logs', [AuditLogController::class, 'index']);
    });
});
