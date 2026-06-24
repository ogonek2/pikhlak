<?php

namespace App\Services\ClientBot;

use App\Models\Bot;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClientTelegramApi
{
    /** @param array<string, mixed> $payload */
    public function sendMessage(Bot $bot, int $chatId, string $text, array $payload = []): ?int
    {
        $token = $bot->telegram_token;
        if (! $token) {
            return null;
        }

        try {
            $response = $this->http()->post("https://api.telegram.org/bot{$token}/sendMessage", array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $payload));
        } catch (\Throwable $e) {
            Log::warning('Client bot sendMessage error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Client bot sendMessage failed', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $json = $response->json();

        return isset($json['result']['message_id']) ? (int) $json['result']['message_id'] : null;
    }

    public function sendDocument(Bot $bot, int $chatId, string $absolutePath, ?string $caption = null): ?int
    {
        $token = $bot->telegram_token;
        if (! $token || ! is_readable($absolutePath)) {
            return null;
        }

        try {
            $response = $this->http()
                ->attach('document', file_get_contents($absolutePath), basename($absolutePath))
                ->post("https://api.telegram.org/bot{$token}/sendDocument", array_filter([
                    'chat_id' => $chatId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]));
        } catch (\Throwable $e) {
            Log::warning('Client bot sendDocument error', ['chat_id' => $chatId, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Client bot sendDocument failed', [
                'chat_id' => $chatId,
                'status' => $response->status(),
            ]);

            return null;
        }

        $json = $response->json();

        return isset($json['result']['message_id']) ? (int) $json['result']['message_id'] : null;
    }

    public function sendPhoto(Bot $bot, int $chatId, string $absolutePath, ?string $caption = null): ?int
    {
        $token = $bot->telegram_token;
        if (! $token || ! is_readable($absolutePath)) {
            return null;
        }

        try {
            $response = $this->http()
                ->attach('photo', file_get_contents($absolutePath), basename($absolutePath))
                ->post("https://api.telegram.org/bot{$token}/sendPhoto", array_filter([
                    'chat_id' => $chatId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]));
        } catch (\Throwable $e) {
            Log::warning('Client bot sendPhoto error', ['chat_id' => $chatId, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        return isset($json['result']['message_id']) ? (int) $json['result']['message_id'] : null;
    }

    private function http(): PendingRequest
    {
        $http = Http::timeout(30);
        if (config('ai.verify_ssl') === false) {
            $http = $http->withOptions(['verify' => false]);
        }

        return $http;
    }
}
