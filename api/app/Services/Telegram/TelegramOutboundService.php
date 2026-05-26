<?php

namespace App\Services\Telegram;

use App\Models\Bot;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\Http;

class TelegramOutboundService
{
    public function sendText(Chat $chat, Bot $bot, string $text, array $meta = []): Message
    {
        $token = $bot->telegram_token;
        if (! $token) {
            throw new \RuntimeException('Telegram token не задан для бота.');
        }

        $http = Http::timeout(30);
        if (config('ai.verify_ssl') === false) {
            $http = $http->withOptions(['verify' => false]);
        }

        $response = $http->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chat->telegram_chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Telegram API: '.$response->body());
        }

        $data = $response->json();
        $telegramMessageId = $data['result']['message_id'] ?? null;

        return Message::query()->create([
            'chat_id' => $chat->id,
            'direction' => 'outbound',
            'telegram_message_id' => $telegramMessageId,
            'type' => 'text',
            'body' => $text,
            'payload' => array_merge($meta, ['sender' => $meta['sender'] ?? 'admin']),
        ]);
    }
}
