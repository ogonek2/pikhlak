<?php

namespace App\Services\ClientBot;

use App\Models\AiProfile;
use App\Models\AiRequestLog;
use App\Models\Bot;
use App\Models\RentalClient;
use App\Services\AI\AiModelNameResolver;
use App\Services\AI\AiProviderFactory;
use App\Services\AI\ClientReplySanitizer;
use App\Services\AI\GuardrailService;
use App\Services\AI\SettingHelper;
use Illuminate\Support\Facades\Log;

class ClientBotAiService
{
    public function __construct(
        private readonly AiProviderFactory $providers,
        private readonly ClientAiContextBuilder $clientContext,
        private readonly ClientReplySanitizer $sanitizer,
        private readonly GuardrailService $guardrails,
        private readonly ClientBotMessageService $messages,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function process(Bot $bot, int $chatId, RentalClient $client, string $userText): array
    {
        if (! $this->isEnabled($bot)) {
            return [];
        }

        $profile = $this->resolveProfile($bot);
        if (! $profile || ! $profile->is_active) {
            return [];
        }

        $behavior = SettingHelper::behavior($bot->project_id);
        if (! ($behavior['auto_reply'] ?? true)) {
            return [];
        }

        $guard = $this->guardrails->check($profile, $userText, false);
        if ($guard['blocked'] ?? false) {
            return $this->buildActions($chatId, $this->sanitizer->clientSafeFallback());
        }

        $systemPrompt = $this->buildSystemPrompt($profile, $client);
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userText],
        ];

        $reply = $this->runModel($profile, $messages);

        if ($reply === null || trim($reply) === '') {
            return $this->buildActions($chatId, $this->sanitizer->clientSafeFallback());
        }

        $reply = $this->sanitizer->sanitizeForClient(trim($reply));
        if ($reply === '') {
            return $this->buildActions($chatId, $this->sanitizer->clientSafeFallback());
        }

        return $this->buildActions($chatId, $reply);
    }

    public function isEnabled(Bot $bot): bool
    {
        if (! config('client_bot_ai.enabled', true)) {
            return false;
        }

        $bot->loadMissing('project');

        return (bool) ($bot->config['ai_enabled'] ?? true);
    }

    private function buildSystemPrompt(AiProfile $profile, RentalClient $client): string
    {
        $parts = [config('client_bot_ai.system_prompt', '')];

        $personality = $profile->personality ?? [];
        if (! empty($personality['tone'])) {
            $parts[] = 'Тон общения: '.$personality['tone'];
        }
        if (! empty($personality['company_info'])) {
            $parts[] = 'О компании: '.$personality['company_info'];
        }

        $parts[] = $this->clientContext->build($client);

        return implode("\n\n", array_filter($parts));
    }

    private function resolveProfile(Bot $bot): ?AiProfile
    {
        return AiProfile::query()
            ->with('model')
            ->where('project_id', $bot->project_id)
            ->where('is_active', true)
            ->where(function ($query) use ($bot) {
                $query->where('bot_id', $bot->id)->orWhere('is_default', true);
            })
            ->orderByRaw('CASE WHEN bot_id = ? THEN 0 ELSE 1 END', [$bot->id])
            ->first();
    }

    /** @param array<int, array{role: string, content: string}> $messages */
    private function runModel(AiProfile $profile, array $messages): ?string
    {
        $model = $profile->model;
        $attempts = $this->buildProviderAttempts($model);
        $lastError = null;

        foreach ($attempts as $attempt) {
            $provider = $this->providers->makeByName($attempt['provider']);
            if (! $provider->isConfigured()) {
                continue;
            }

            try {
                $result = $provider->chat(
                    $messages,
                    (float) $profile->temperature,
                    (int) $profile->max_tokens,
                    $attempt['model']
                );
                $content = trim($result['content'] ?? '');
                if ($content !== '') {
                    return $content;
                }
            } catch (\Throwable $e) {
                $lastError = $e;
                Log::warning('Client bot AI provider error', [
                    'provider' => $attempt['provider'],
                    'model' => $attempt['model'],
                    'error' => mb_substr($e->getMessage(), 0, 500),
                ]);
            }
        }

        if ($lastError) {
            AiRequestLog::query()->create([
                'chat_id' => null,
                'model_id' => $model?->id ?? $profile->model_id,
                'status' => 'failed',
                'error' => $lastError->getMessage(),
            ]);
        }

        return null;
    }

    /** @return list<array{provider: string, model: string}> */
    private function buildProviderAttempts(?\App\Models\AiModel $primary): array
    {
        $attempts = [];
        $seen = [];

        $add = function (string $provider, string $model) use (&$attempts, &$seen): void {
            $key = $provider.'|'.$model;
            if ($model === '' || isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $attempts[] = ['provider' => $provider, 'model' => $model];
        };

        if ($primary) {
            foreach ($this->providers->fallbackModelNames($primary->provider, AiModelNameResolver::resolve($primary)) as $name) {
                $add($primary->provider, $name);
            }
        }

        foreach (['groq', 'gemini', 'openai'] as $provider) {
            if ($primary && $primary->provider === $provider) {
                continue;
            }
            if (! $this->providers->makeByName($provider)->isConfigured()) {
                continue;
            }
            foreach ($this->providers->fallbackModelNames($provider) as $name) {
                $add($provider, $name);
            }
        }

        return $attempts;
    }

    /** @return array<int, array<string, mixed>> */
    private function buildActions(int $chatId, string $text): array
    {
        return [
            ['type' => 'typing', 'chat_id' => $chatId, 'duration' => 1],
            [
                'type' => 'send_message',
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => $this->messages->linkedMenu(),
            ],
        ];
    }
}
