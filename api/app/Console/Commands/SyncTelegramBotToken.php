<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\Telegram\TelegramBotProfileService;
use Illuminate\Console\Command;

class SyncTelegramBotToken extends Command
{
    protected $signature = 'pikhlak:sync-bot-token {--uuid= : Bot UUID}';

    protected $description = 'Sync TELEGRAM_BOT_TOKEN from .env to bots table';

    public function handle(): int
    {
        $token = config('pikhlak.telegram_bot_token');

        if (! $token) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');

            return self::FAILURE;
        }

        $query = Bot::query()->where('is_active', true);

        if ($uuid = $this->option('uuid')) {
            $query->where('uuid', $uuid);
        }

        $bot = $query->first();

        if (! $bot) {
            $this->error('No active bot found. Run: php artisan db:seed');

            return self::FAILURE;
        }

        $bot->update(['telegram_token' => $token]);

        try {
            $bot = app(TelegramBotProfileService::class)->sync($bot);
            $username = $bot->config['telegram_username'] ?? '?';
            $this->info("Token synced to bot: {$bot->name} ({$bot->uuid})");
            $this->info("Telegram @{$username} — реферальные ссылки: t.me/{$username}?start=КОД");
        } catch (\Throwable $e) {
            $this->warn('Token saved, but getMe failed: '.$e->getMessage());
        }

        return self::SUCCESS;
    }
}
