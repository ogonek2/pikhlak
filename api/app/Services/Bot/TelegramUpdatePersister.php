<?php

namespace App\Services\Bot;

use App\Models\Bot;
use App\Models\Chat;
use App\Models\Message;
use App\Models\TelegramUser;

class TelegramUpdatePersister
{
    public function persist(Bot $bot, array $update): ?Chat
    {
        $message = $update['message'] ?? $update['callback_query']['message'] ?? null;
        if (! $message) {
            return null;
        }

        $from = $update['message']['from'] ?? $update['callback_query']['from'] ?? null;
        $chatData = $message['chat'] ?? null;

        if (! $from || ! $chatData) {
            return null;
        }

        $telegramUser = TelegramUser::query()->updateOrCreate(
            ['bot_id' => $bot->id, 'telegram_id' => $from['id']],
            [
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'language_code' => $from['language_code'] ?? null,
            ]
        );

        $chat = Chat::query()->updateOrCreate(
            ['bot_id' => $bot->id, 'telegram_chat_id' => $chatData['id']],
            [
                'telegram_user_id' => $telegramUser->id,
                'type' => $chatData['type'] ?? 'private',
                'last_activity_at' => now(),
            ]
        );

        $body = $update['message']['text'] ?? $update['callback_query']['data'] ?? null;

        Message::query()->create([
            'chat_id' => $chat->id,
            'direction' => 'inbound',
            'telegram_message_id' => $message['message_id'] ?? null,
            'type' => isset($update['callback_query']) ? 'callback' : 'text',
            'body' => $body,
            'payload' => ['sender' => 'user', 'raw' => $update],
        ]);

        return $chat;
    }
}
