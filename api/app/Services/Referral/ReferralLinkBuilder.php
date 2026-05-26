<?php

namespace App\Services\Referral;

use App\Models\Bot;
use App\Models\ReferralLink;
use App\Services\Telegram\TelegramBotProfileService;
use Illuminate\Support\Str;

class ReferralLinkBuilder
{
    public function __construct(
        private readonly TelegramBotProfileService $botProfile,
    ) {}

    public function generateCode(?string $preferred, int $projectId, int $botId): string
    {
        if ($preferred) {
            $code = $this->sanitizeCode($preferred);
            if ($code !== '' && ! $this->codeExists($botId, $code)) {
                return $code;
            }
        }

        do {
            $code = 'pk_'.Str::lower(Str::random(8));
        } while ($this->codeExists($botId, $code));

        return $code;
    }

    public function sanitizeCode(string $code): string
    {
        $code = preg_replace('/[^A-Za-z0-9_-]/', '', $code) ?? '';

        return substr($code, 0, 64);
    }

    /**
     * Deep-link только для бота Pikhlak этого проекта: https://t.me/{username}?start={code}
     */
    public function telegramUrl(Bot $bot, ReferralLink $link): ?string
    {
        if ($link->bot_id && $link->bot_id !== $bot->id) {
            return null;
        }

        $username = $this->botProfile->username($bot);
        if (! $username) {
            return null;
        }

        return "https://t.me/{$username}?start={$link->code}";
    }

    public function codeExists(int $botId, string $code): bool
    {
        return ReferralLink::query()
            ->where('bot_id', $botId)
            ->where('code', $code)
            ->exists();
    }
}
