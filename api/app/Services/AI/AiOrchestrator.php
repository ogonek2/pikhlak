<?php

namespace App\Services\AI;

use App\Models\Bot;
use App\Models\Chat;

/**
 * @deprecated Используйте AiKernel. Оставлен для обратной совместимости.
 */
class AiOrchestrator
{
    public function __construct(private readonly AiKernel $kernel) {}

    public function process(Bot $bot, Chat $chat, string $userText): array
    {
        return $this->kernel->process($bot, $chat, $userText);
    }
}
