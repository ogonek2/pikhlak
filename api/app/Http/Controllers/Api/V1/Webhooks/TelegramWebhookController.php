<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Api\ApiController;
use App\Models\Bot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends ApiController
{
    public function handle(Request $request, string $botUuid): JsonResponse
    {
        $bot = Bot::query()->where('uuid', $botUuid)->where('is_active', true)->first();

        if (! $bot) {
            return $this->error('Bot not found.', 'bot_not_found', 404);
        }

        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($bot->webhook_secret && $secret !== $bot->webhook_secret) {
            return $this->error('Invalid webhook secret.', 'unauthorized', 401);
        }

        $updateId = $request->input('update_id');

        return $this->success([
            'accepted' => true,
            'bot_id' => $bot->id,
            'update_id' => $updateId,
            ...$this->stub('Update queued for async processing'),
        ]);
    }
}
