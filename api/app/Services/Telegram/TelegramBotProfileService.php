<?php

namespace App\Services\Telegram;

use App\Models\Bot;
use Illuminate\Support\Facades\Http;

class TelegramBotProfileService
{
    public function sync(Bot $bot): Bot
    {
        $token = $bot->telegram_token;
        if (! $token) {
            throw new \RuntimeException('У бота Pikhlak не задан Telegram token. Выполните: php artisan pikhlak:sync-bot-token');
        }

        $http = Http::timeout(15);
        if (config('ai.verify_ssl') === false) {
            $http = $http->withOptions(['verify' => false]);
        }

        $response = $http->get("https://api.telegram.org/bot{$token}/getMe");
        if (! $response->successful()) {
            throw new \RuntimeException('Telegram getMe: '.$response->body());
        }

        $result = $response->json('result');
        if (! is_array($result) || empty($result['username'])) {
            throw new \RuntimeException('Telegram не вернул username бота. Проверьте токен.');
        }

        $config = $bot->config ?? [];
        $config['telegram_username'] = $result['username'];
        $config['telegram_id'] = $result['id'] ?? null;
        $config['telegram_first_name'] = $result['first_name'] ?? null;
        $config['profile_synced_at'] = now()->toIso8601String();

        $bot->update(['config' => $config]);

        return $bot->fresh();
    }

    public function username(Bot $bot): ?string
    {
        $username = $bot->config['telegram_username'] ?? null;

        return $username ? ltrim((string) $username, '@') : null;
    }

    public function matchesStartCommand(Bot $bot, string $rawText): bool
    {
        if (! preg_match('/^\/start@(\w+)/iu', trim($rawText), $m)) {
            return true;
        }

        $mentioned = strtolower($m[1]);
        $ours = strtolower($this->username($bot) ?? '');

        return $ours !== '' && $mentioned === $ours;
    }
}
