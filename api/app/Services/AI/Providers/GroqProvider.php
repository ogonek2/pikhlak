<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiModelNameResolver;
use App\Services\AI\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class GroqProvider implements AiProviderInterface
{
    public function chat(array $messages, float $temperature, int $maxTokens, ?string $modelName = null): array
    {
        $key = config('ai.providers.groq.api_key');
        $model = $modelName ?? AiModelNameResolver::resolve(null, 'groq');
        $start = microtime(true);

        $http = Http::withToken($key)->timeout(60);
        if (! config('ai.verify_ssl')) {
            $http = $http->withOptions(['verify' => false]);
        }
        $response = $http->post(config('ai.providers.groq.base_url').'/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Groq API error: '.$response->body());
        }

        $data = $response->json();
        $latency = (int) ((microtime(true) - $start) * 1000);

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_in' => $data['usage']['prompt_tokens'] ?? 0,
            'tokens_out' => $data['usage']['completion_tokens'] ?? 0,
            'latency_ms' => $latency,
        ];
    }

    public function isConfigured(): bool
    {
        return ! empty(config('ai.providers.groq.api_key'));
    }
}
