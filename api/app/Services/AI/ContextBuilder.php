<?php

namespace App\Services\AI;

use App\Models\AiContextMemory;
use App\Models\Chat;
use App\Models\Message;

class ContextBuilder
{
    public function buildMessages(string $systemPrompt, Chat $chat, string $userMessage, int $maxMessages): array
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        $memory = AiContextMemory::query()->where('chat_id', $chat->id)->first();
        if ($memory?->summary) {
            $messages[] = ['role' => 'system', 'content' => 'Краткая память диалога: '.$memory->summary];
        }

        $history = Message::query()
            ->where('chat_id', $chat->id)
            ->whereIn('direction', ['inbound', 'outbound'])
            ->latest()
            ->limit($maxMessages)
            ->get()
            ->reverse();

        foreach ($history as $msg) {
            if (! $msg->body) {
                continue;
            }
            $messages[] = [
                'role' => $msg->direction === 'inbound' ? 'user' : 'assistant',
                'content' => $msg->body,
            ];
        }

        if (! $history->contains(fn ($m) => $m->body === $userMessage && $m->direction === 'inbound')) {
            $messages[] = ['role' => 'user', 'content' => $userMessage];
        }

        return $messages;
    }
}
