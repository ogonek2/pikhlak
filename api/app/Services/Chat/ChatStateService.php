<?php

namespace App\Services\Chat;

use App\Models\Chat;

class ChatStateService
{
    public const MODE_AI = 'ai';

    public const MODE_HUMAN = 'human';

    public function getMode(Chat $chat): string
    {
        $state = $chat->state ?? [];

        return ($state['reply_mode'] ?? self::MODE_AI) === self::MODE_HUMAN
            ? self::MODE_HUMAN
            : self::MODE_AI;
    }

    public function isHuman(Chat $chat): bool
    {
        return $this->getMode($chat) === self::MODE_HUMAN;
    }

    public function setMode(Chat $chat, string $mode, ?int $userId = null): void
    {
        $state = $chat->state ?? [];
        $state['reply_mode'] = $mode === self::MODE_HUMAN ? self::MODE_HUMAN : self::MODE_AI;
        $state['reply_mode_changed_at'] = now()->toIso8601String();
        if ($userId) {
            $state['assigned_user_id'] = $userId;
        }
        $chat->update(['state' => $state]);
    }
}
