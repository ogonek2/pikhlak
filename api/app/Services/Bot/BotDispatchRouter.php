<?php

namespace App\Services\Bot;

use App\Models\Bot;
use App\Services\ClientBot\ClientBotDispatcher;

class BotDispatchRouter
{
    public function __construct(
        private readonly BotDispatcher $warming,
        private readonly ClientBotDispatcher $client,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function dispatch(Bot $bot, array $update): array
    {
        if ($bot->isClient()) {
            return $this->client->dispatch($bot, $update);
        }

        return $this->warming->dispatch($bot, $update);
    }
}
