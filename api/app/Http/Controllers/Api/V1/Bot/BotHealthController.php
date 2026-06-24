<?php

namespace App\Http\Controllers\Api\V1\Bot;

use App\Http\Controllers\Api\ApiController;
use App\Models\Bot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotHealthController extends ApiController
{
    public function config(Request $request): JsonResponse
    {
        /** @var Bot $bot */
        $bot = $request->attributes->get('bot');

        if (! $bot->telegram_token) {
            return $this->error('Задайте токен бота в админке и сохраните.', 'telegram_token_missing', 422);
        }

        return $this->success([
            'bot_uuid' => $bot->uuid,
            'type' => $bot->type,
            'name' => $bot->name,
            'mode' => $bot->mode,
            'is_active' => $bot->is_active,
            'telegram_token' => $bot->telegram_token,
            'telegram_username' => $bot->config['telegram_username'] ?? null,
            'api_version' => '1.0.0',
            'keyboards_version' => 1,
            'rate_limit_per_minute' => 120,
        ]);
    }
}
