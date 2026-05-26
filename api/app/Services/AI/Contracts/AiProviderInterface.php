<?php

namespace App\Services\AI\Contracts;

interface AiProviderInterface
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, tokens_in: int, tokens_out: int, latency_ms: int}
     */
    public function chat(array $messages, float $temperature, int $maxTokens, ?string $modelName = null): array;

    public function isConfigured(): bool;
}
