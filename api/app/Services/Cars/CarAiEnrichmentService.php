<?php

namespace App\Services\Cars;

use App\Models\AiProfile;
use App\Models\Car;
use App\Models\Project;
use App\Services\AI\AiProviderFactory;
use Illuminate\Support\Facades\Log;

class CarAiEnrichmentService
{
    public function __construct(
        private readonly AiProviderFactory $providers,
    ) {}

    public function isAvailable(): bool
    {
        return $this->providers->firstAvailable() !== null;
    }

    /**
     * @return array{ok: bool, message: string, meta?: array}
     */
    public function enrich(Car $car, Project $project, bool $overwriteDescription = false): array
    {
        $provider = $this->providers->firstAvailable();
        if (! $provider) {
            return ['ok' => false, 'message' => 'ИИ не настроен: добавьте GROQ_API_KEY или другой провайдер в .env'];
        }

        $profile = AiProfile::query()
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->where('is_default', true)
            ->with('model')
            ->first();

        $temperature = (float) ($profile?->temperature ?? 0.4);
        $maxTokens = min(2500, (int) ($profile?->max_tokens ?? 1500));

        $system = <<<'SYS'
Ты — контент-менеджер автосалона Pikhlak Auto (импорт авто под ключ, Telegram-бот).
По данным автомобиля сгенерируй материалы для CRM, бота и поиска.

Ответь ТОЛЬКО валидным JSON (без markdown), структура:
{
  "description": "продающее описание 3-5 предложений, HTML можно <b>",
  "description_short": "одна строка для карточки",
  "keywords": ["10-20 слов для поиска: ru, uk, en, опечатки"],
  "search_aliases": ["как клиенты пишут в чате: сонаты, хендай к5..."],
  "bot_context": "2-4 предложения: что говорить ИИ-ассистенту про это авто, акценты продажи",
  "photo_caption": "подпись к альбому фото в Telegram, HTML, 1-2 предложения",
  "referral_hints": ["3 коротких названия для реф. ссылок"],
  "filters": {
    "body_type": "sedan|suv|hatchback|...",
    "fuel": "gasoline|diesel|hybrid|electric",
    "transmission_type": "automatic|manual",
    "drive": "fwd|rwd|awd",
    "color": "цвет на русском",
    "tags": ["тег1", "тег2"]
  },
  "client_questions": ["3 типичных вопроса клиента"],
  "photo_prompt_phrases": ["фразы для запроса фото: фото sonata, покажи сонату..."]
}

Не выдумывай VIN и точные характеристики, которых нет во входных данных. Цену упоминай только если передана.
SYS;

        $user = $this->buildUserPayload($car);

        try {
            $result = $provider->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ], $temperature, $maxTokens);

            $parsed = $this->parseJson($result['content'] ?? '');
            if ($parsed === null) {
                return ['ok' => false, 'message' => 'ИИ вернул неверный формат ответа'];
            }

            $this->applyToCar($car, $parsed, $overwriteDescription);

            return [
                'ok' => true,
                'message' => 'ИИ сгенерировал описание, ключевые слова, фильтры и промпты для бота.',
                'meta' => $car->fresh()->ai_meta,
            ];
        } catch (\Throwable $e) {
            Log::warning('Car AI enrichment failed', ['car_id' => $car->id, 'error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Ошибка ИИ: '.$e->getMessage()];
        }
    }

    private function buildUserPayload(Car $car): string
    {
        $lines = [
            'Марка: '.$car->make,
            'Модель: '.$car->model,
            'Год: '.($car->year ?? 'не указан'),
            'Цена: '.($car->price ? $car->formattedPrice() : 'по запросу'),
            'Статус: '.$car->status,
            'VIN: '.($car->vin ?? '—'),
        ];

        if ($car->description) {
            $lines[] = 'Черновик описания от менеджера: '.$car->description;
        }

        foreach ($car->specs ?? [] as $k => $v) {
            if ($v) {
                $lines[] = "Характеристика {$k}: {$v}";
            }
        }

        if ($car->category) {
            $lines[] = 'Категория: '.$car->category->name;
        }

        return implode("\n", $lines);
    }

    private function applyToCar(Car $car, array $parsed, bool $overwriteDescription): void
    {
        $meta = [
            'description_short' => $parsed['description_short'] ?? null,
            'keywords' => array_values(array_filter((array) ($parsed['keywords'] ?? []))),
            'search_aliases' => array_values(array_filter((array) ($parsed['search_aliases'] ?? []))),
            'bot_context' => $parsed['bot_context'] ?? null,
            'photo_caption' => $parsed['photo_caption'] ?? null,
            'referral_hints' => array_values(array_filter((array) ($parsed['referral_hints'] ?? []))),
            'filters' => is_array($parsed['filters'] ?? null) ? $parsed['filters'] : [],
            'client_questions' => array_values(array_filter((array) ($parsed['client_questions'] ?? []))),
            'photo_prompt_phrases' => array_values(array_filter((array) ($parsed['photo_prompt_phrases'] ?? []))),
            'generated_at' => now()->toIso8601String(),
        ];

        $updates = ['ai_meta' => $meta];

        $newDescription = trim((string) ($parsed['description'] ?? ''));
        if ($newDescription !== '' && ($overwriteDescription || ! trim((string) $car->description))) {
            $updates['description'] = $newDescription;
        }

        $car->update($updates);

        $this->syncAttributes($car, $meta);
    }

    private function syncAttributes(Car $car, array $meta): void
    {
        // Полные тексты — только в cars.ai_meta; здесь короткие поля для фильтров/поиска в админке.
        $pairs = [
            'ai_keywords' => $this->truncateText(implode(', ', $meta['keywords'] ?? []), 800),
            'ai_search_aliases' => $this->truncateText(implode(', ', $meta['search_aliases'] ?? []), 800),
        ];

        foreach ($meta['filters'] ?? [] as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if (is_string($value) || is_numeric($value)) {
                $pairs['filter_'.$key] = $this->truncateText((string) $value, 200);
            }
        }

        $car->attributes()
            ->whereIn('key', ['ai_bot_context', 'ai_photo_caption'])
            ->delete();

        foreach ($pairs as $key => $value) {
            if ($value === '') {
                $car->attributes()->where('key', $key)->delete();
                continue;
            }
            $car->attributes()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    private function truncateText(string $text, int $max): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }

    private function parseJson(string $content): ?array
    {
        $content = trim($content);
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/u', $content, $m)) {
            $content = trim($m[1]);
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }
}
