<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Services\AI\Contracts\AiProviderInterface;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\GroqProvider;
use App\Services\AI\Providers\OpenAiProvider;

class AiProviderFactory
{
    public function make(?AiModel $model = null): AiProviderInterface
    {
        $provider = $model?->provider ?? config('ai.default_provider');

        return match ($provider) {
            'gemini' => app(GeminiProvider::class),
            'openai' => app(OpenAiProvider::class),
            default => app(GroqProvider::class),
        };
    }

    public function firstAvailable(): ?AiProviderInterface
    {
        foreach (['groq', 'gemini', 'openai'] as $name) {
            $provider = $this->makeByName($name);
            if ($provider->isConfigured()) {
                return $provider;
            }
        }

        return null;
    }

    public function makeByName(string $provider): AiProviderInterface
    {
        return match ($provider) {
            'gemini' => app(GeminiProvider::class),
            'openai' => app(OpenAiProvider::class),
            default => app(GroqProvider::class),
        };
    }

    /** @return list<string> */
    public function fallbackModelNames(string $provider, ?string $primary = null): array
    {
        $models = config("ai.providers.{$provider}.fallback_models", []);
        $default = config("ai.providers.{$provider}.model");
        $list = array_values(array_unique(array_filter([
            $primary,
            $default,
            ...$models,
        ])));

        return $list;
    }
}
