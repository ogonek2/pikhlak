<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiModelNameResolver;
use App\Services\AI\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProviderInterface
{
    public function chat(array $messages, float $temperature, int $maxTokens, ?string $modelName = null): array
    {
        $key = config('ai.providers.gemini.api_key');
        $model = $modelName ?? AiModelNameResolver::resolve(null, 'gemini');
        $start = microtime(true);

        $contents = [];
        $system = '';
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system .= $msg['content']."\n";
                continue;
            }
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ],
        ];
        if ($system !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => trim($system)]]];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $http = Http::timeout(60);
        if (! config('ai.verify_ssl')) {
            $http = $http->withOptions(['verify' => false]);
        }
        $response = $http->post($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Gemini API error: '.$response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $latency = (int) ((microtime(true) - $start) * 1000);

        return [
            'content' => $text,
            'tokens_in' => $data['usageMetadata']['promptTokenCount'] ?? 0,
            'tokens_out' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
            'latency_ms' => $latency,
        ];
    }

    public function isConfigured(): bool
    {
        return ! empty(config('ai.providers.gemini.api_key'));
    }
}
