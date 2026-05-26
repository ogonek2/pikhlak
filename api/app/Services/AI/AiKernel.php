<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiProfile;
use App\Models\AiRequestLog;
use App\Models\AiResponseTemplate;
use App\Models\AiRoute;
use App\Models\Bot;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;
use App\Services\Cars\CarCatalogService;
use App\Services\CRM\LeadWarmingService;
use Illuminate\Support\Facades\Log;

/**
 * Единое ядро ИИ: маршрутизация, данные проекта, pipeline, ответ в Telegram.
 */
class AiKernel
{
    private ?string $lastAiErrorCode = null;

    public function __construct(
        private readonly AiProviderFactory $providers,
        private readonly PromptCompiler $compiler,
        private readonly GuardrailService $guardrails,
        private readonly FaqRetriever $faq,
        private readonly ContextBuilder $context,
        private readonly LeadWarmingService $warming,
        private readonly AiRouteResolver $routeResolver,
        private readonly ProjectDataContextService $dataContext,
        private readonly CarCatalogService $catalog,
        private readonly ClientReplySanitizer $replySanitizer,
    ) {}

    public function process(Bot $bot, Chat $chat, string $userText): array
    {
        $project = $bot->project;
        $behavior = SettingHelper::behavior($project->id);

        if (! ($behavior['auto_reply'] ?? true)) {
            return [];
        }

        $route = $this->routeResolver->resolve($project->id, $userText);
        $profile = $this->resolveProfile($project, $route);
        if (! $profile || ! $profile->is_active) {
            return [];
        }

        $lead = $this->warming->ensureLead($chat, $project);
        $strictAllowed = (bool) ($behavior['strict_allowed_topics'] ?? false);
        $guard = $this->guardrails->check($profile, $userText, $strictAllowed);

        if ($guard['blocked'] ?? false) {
            return $this->blockedResponse($chat, $profile, $lead);
        }

        if ($behavior['use_faq_first'] ?? true) {
            $match = $this->faq->findBest($project->id, $chat->id, $userText);
            if ($match) {
                return $this->messageActions($chat, $match['item']->answer, $lead, $userText);
            }
        }

        if ($this->catalog->isPhotoRequest($userText)) {
            $photoCar = $this->catalog->resolveCarForPhotoRequest($project->id, $userText, $lead);
            if (! $photoCar) {
                $candidates = $this->catalog->findCarCandidates($project->id, $userText, 3);
                if ($candidates->count() === 1) {
                    $photoCar = $candidates->first();
                }
            }
            if ($photoCar) {
                $photoCar->load('media');
                $lead->update(['car_interest_id' => $photoCar->id]);

                return $this->wrapActionsWithLead(
                    $this->catalog->buildCarPhotoAlbumActions((int) $chat->telegram_chat_id, $photoCar),
                    $chat,
                    $lead,
                    $userText,
                    false
                );
            }
        }

        if ($this->catalog->isCatalogInquiry($userText)) {
            $cars = $this->catalog->publishedForProject($project->id, null, 15);
            if ($cars->isNotEmpty()) {
                return $this->wrapActionsWithLead(
                    $this->catalog->buildTelegramCatalogActions($project, (int) $chat->telegram_chat_id),
                    $chat,
                    $lead,
                    $userText
                );
            }
        }

        $interested = $this->catalog->findInterestedCar($project->id, $userText);
        if ($interested) {
            $lead->update(['car_interest_id' => $interested->id]);
        }

        $dataSources = $this->resolveDataSources($route, $behavior);
        $warmingStep = $this->warming->resolveWarmingStep($lead, $project->id);
        $dataBlock = $this->dataContext->build($project->id, $dataSources, $userText, $lead);

        $systemPrompt = $this->compiler->compile($profile, $lead, $warmingStep, $dataBlock, $route?->extra_instruction, $userText);
        $messages = $this->context->buildMessages(
            $systemPrompt,
            $chat,
            $userText,
            min(8, (int) ($behavior['max_context_messages'] ?? 12))
        );

        $pipeline = AiPipelineNormalizer::fromArray($route?->pipeline);
        if ($pipeline !== null) {
            $reply = $this->runPipeline($pipeline, $messages, $profile, $chat);
        } else {
            $model = $route?->model ?? $profile->model;
            $reply = $this->runSingleModel($model, $profile, $messages, $chat);
        }

        if ($reply === null) {
            return $this->fallbackActions($chat, $profile, $this->lastAiErrorCode ?? 'api_error');
        }

        $reply = $this->replySanitizer->sanitize(trim($reply));
        if ($reply === '') {
            return $this->fallbackActions($chat, $profile, 'empty_response');
        }

        $this->logSuccess($chat, $profile, $systemPrompt);
        $this->persistReply($chat, $profile, $lead, $reply, $userText);

        $interestedAfter = $this->catalog->findInterestedCar($project->id, $userText.' '.$reply);
        if ($interestedAfter) {
            $lead->update(['car_interest_id' => $interestedAfter->id]);
        }

        return $this->messageActions($chat, $reply, $lead, $userText, false);
    }

    private function wrapActionsWithLead(array $actions, Chat $chat, $lead, string $userText, bool $score = true): array
    {
        if ($score && $userText !== '') {
            $this->warming->scoreFromMessage($lead, $userText, 'catalog');
        }

        return $actions;
    }

    public function processPlayground(Project $project, AiProfile $profile, string $userText, ?AiRoute $route = null): string
    {
        $route ??= $this->routeResolver->resolve($project->id, $userText);
        $behavior = SettingHelper::behavior($project->id);
        $dataSources = $this->resolveDataSources($route, $behavior);
        $dataBlock = $this->dataContext->build($project->id, $dataSources, $userText);
        $system = $this->compiler->compile($profile, null, null, $dataBlock, $route?->extra_instruction, $userText);

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userText],
        ];

        $pipeline = AiPipelineNormalizer::fromArray($route?->pipeline);
        if ($pipeline !== null) {
            return $this->runPipeline($pipeline, $messages, $profile) ?? 'Ошибка pipeline';
        }

        $model = $route?->model ?? $profile->model;

        return $this->runSingleModel($model, $profile, $messages) ?? 'Ошибка провайдера';
    }

    private function runPipeline(array $pipeline, array $messages, AiProfile $profile, ?Chat $chat = null): ?string
    {
        $content = null;

        foreach ($pipeline as $step) {
            if (! is_array($step)) {
                continue;
            }

            $stepModel = \App\Models\AiModel::query()->find($step['model_id'] ?? null)
                ?? $profile->model;
            $instruction = $step['instruction'] ?? '';

            $stepMessages = $messages;
            if ($instruction) {
                $stepMessages[0]['content'] = ($stepMessages[0]['content'] ?? '')."\n\n[Шаг pipeline: {$instruction}]";
            }
            if ($content !== null) {
                $stepMessages[] = ['role' => 'assistant', 'content' => $content];
                $stepMessages[] = ['role' => 'user', 'content' => 'Обработай предыдущий ответ согласно инструкции шага.'];
            }

            $content = $this->runSingleModel($stepModel, $profile, $stepMessages, $chat);
            if ($content === null) {
                return null;
            }
        }

        return $content;
    }

    private function runSingleModel($model, AiProfile $profile, array $messages, ?Chat $chat = null): ?string
    {
        $this->lastAiErrorCode = null;
        $attempts = $this->buildProviderAttempts($model);
        $lastError = null;

        foreach ([$messages, $this->compactMessages($messages)] as $batch) {
            if ($batch === []) {
                continue;
            }

            foreach ($attempts as $attempt) {
                $provider = $this->providers->makeByName($attempt['provider']);
                if (! $provider->isConfigured()) {
                    continue;
                }

                try {
                    $result = $provider->chat(
                        $batch,
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
                    $this->classifyAiError($e);
                    Log::warning('AI provider error', [
                        'provider' => $attempt['provider'],
                        'model' => $attempt['model'],
                        'error' => mb_substr($e->getMessage(), 0, 500),
                    ]);
                }
            }
        }

        if ($chat && $lastError) {
            AiRequestLog::query()->create([
                'chat_id' => $chat->id,
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

    /** @param  array<int, array{role: string, content: string}>  $messages */
    private function compactMessages(array $messages): array
    {
        if (count($messages) <= 4) {
            return [];
        }

        $system = $messages[0] ?? null;
        $rest = array_slice($messages, -3);
        $compact = $system ? [$system] : [];

        return array_merge($compact, $rest);
    }

    private function classifyAiError(\Throwable $e): void
    {
        $msg = strtolower($e->getMessage());
        if (
            str_contains($msg, 'rate_limit')
            || str_contains($msg, 'quota exceeded')
            || str_contains($msg, 'quota')
            || str_contains($msg, 'limit: 0')
        ) {
            $this->lastAiErrorCode = 'rate_limit';
        }
    }

    private function resolveDataSources(?AiRoute $route, array $behavior): array
    {
        $fromRoute = $route?->data_sources;
        if (is_array($fromRoute) && $fromRoute !== []) {
            return $fromRoute;
        }

        if ($behavior['use_project_data'] ?? true) {
            return ['cars', 'faq', 'leads'];
        }

        return [];
    }

    private function resolveProfile(Project $project, ?AiRoute $route): ?AiProfile
    {
        if ($route?->profile_id) {
            $profile = AiProfile::query()
                ->where('id', $route->profile_id)
                ->where('project_id', $project->id)
                ->where('is_active', true)
                ->with(['model', 'forbiddenTopics', 'allowedTopics', 'promptRules'])
                ->first();
            if ($profile) {
                return $profile;
            }
        }

        return AiProfile::query()
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->with(['model', 'forbiddenTopics', 'allowedTopics', 'promptRules'])
            ->first();
    }

    private function persistReply(Chat $chat, AiProfile $profile, $lead, string $reply, string $userText): void
    {
        $conversation = AiConversation::query()->firstOrCreate(
            ['chat_id' => $chat->id, 'profile_id' => $profile->id],
            ['lead_id' => $lead->id, 'started_at' => now()]
        );

        $aiMsg = AiMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        Message::query()->create([
            'chat_id' => $chat->id,
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $reply,
            'ai_message_id' => $aiMsg->id,
            'payload' => ['sender' => 'ai'],
        ]);

        $this->warming->scoreFromMessage($lead, $userText, $reply);
        $this->tryLinkCarInterest($lead, $userText);
    }

    private function tryLinkCarInterest($lead, string $userText): void
    {
        if ($lead->car_interest_id) {
            return;
        }

        $car = $this->catalog->findInterestedCar($lead->project_id, $userText);
        if ($car) {
            $lead->update(['car_interest_id' => $car->id]);
        }
    }

    private function logSuccess(Chat $chat, AiProfile $profile, string $systemPrompt): void
    {
        AiRequestLog::query()->create([
            'chat_id' => $chat->id,
            'model_id' => $profile->model_id,
            'prompt_hash' => hash('sha256', $systemPrompt),
            'status' => 'success',
            'latency_ms' => 0,
            'cost_usd' => 0,
        ]);
    }

    private function messageActions(Chat $chat, string $text, $lead, string $userText, bool $score = true): array
    {
        $text = $this->replySanitizer->sanitize($text);

        if ($score && $userText !== '') {
            $this->warming->scoreFromMessage($lead, $userText, $text);
        }

        return [
            ['type' => 'typing', 'chat_id' => $chat->telegram_chat_id, 'duration' => 1],
            [
                'type' => 'send_message',
                'chat_id' => $chat->telegram_chat_id,
                'text' => $text,
                'parse_mode' => 'HTML',
            ],
        ];
    }

    private function blockedResponse(Chat $chat, AiProfile $profile, $lead): array
    {
        $template = $this->resolveTemplate($profile->id, 'blocked');
        $text = $template?->template
            ?? 'Извините, я не могу помочь с этим запросом. Могу рассказать об автомобилях и доставке Pikhlak.';

        return $this->messageActions($chat, $text, $lead, '');
    }

    private function fallbackActions(Chat $chat, AiProfile $profile, string $code): array
    {
        $template = $this->resolveTemplate($profile->id, $code)
            ?? $this->resolveTemplate($profile->id, 'fallback');
        $text = $this->replySanitizer->sanitize(
            $template?->template
            ?? 'Спасибо за сообщение! Менеджер Pikhlak скоро подключится. Напишите марку авто и бюджет.'
        );

        return [
            ['type' => 'typing', 'chat_id' => $chat->telegram_chat_id, 'duration' => 1],
            [
                'type' => 'send_message',
                'chat_id' => $chat->telegram_chat_id,
                'text' => $text,
                'parse_mode' => 'HTML',
            ],
        ];
    }

    private function resolveTemplate(int $profileId, string $code): ?AiResponseTemplate
    {
        return AiResponseTemplate::query()
            ->where('profile_id', $profileId)
            ->where('code', $code)
            ->orderByRaw("CASE locale WHEN 'ru' THEN 0 WHEN 'uk' THEN 1 ELSE 2 END")
            ->first();
    }
}
