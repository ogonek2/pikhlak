<?php

namespace Database\Seeders;

use App\Models\AiAllowedTopic;
use App\Models\AiForbiddenTopic;
use App\Models\AiModel;
use App\Models\AiProfile;
use App\Models\AiResponseTemplate;
use App\Models\AiSystemPrompt;
use App\Models\AiWarmingScenario;
use App\Models\Project;
use App\Services\AI\SettingHelper;
use Illuminate\Database\Seeder;

class AiDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->where('slug', config('pikhlak.default_project_slug', 'pikhlak'))->first();
        if (! $project) {
            return;
        }

        $groq = AiModel::query()->firstOrCreate(
            ['provider' => 'groq', 'model_name' => config('ai.providers.groq.model')],
            ['is_active' => true]
        );

        $profile = AiProfile::query()->updateOrCreate(
            ['project_id' => $project->id, 'is_default' => true],
            [
                'name' => 'Pikhlak Lead Warming AI',
                'model_id' => $groq->id,
                'temperature' => 0.65,
                'max_tokens' => 800,
                'is_active' => true,
                'personality' => [
                    'role' => 'Консультант Pikhlak Auto по подбору и доставке автомобилей',
                    'tone' => 'дружелюбный, уверенный, без давления',
                    'company_info' => 'Pikhlak Auto — подбор авто из США и Европы, растаможка, доставка, полное сопровождение.',
                    'sales_instructions' => 'Цель — прогреть лида: узнай бюджет, марку/класс авто, срок покупки, город. Предложи расчёт доставки. Мягко запроси контакт для менеджера при высоком интересе.',
                ],
            ]
        );

        if (! $profile->prompts()->where('is_published', true)->exists()) {
            $profile->prompts()->create([
                'version' => 1,
                'content' => <<<'PROMPT'
Ты — AI-ассистент автосервиса Pikhlak Auto в Telegram.

Задача: прогрев лида (lead warming) — консультация по покупке авто с доставкой.

Правила:
- Отвечай кратко (2-4 предложения), на языке пользователя (украинский/русский).
- Не выдумывай цены — предложи уточнить бюджет или расчёт.
- Не обсуждай политику, медицину, конкурентов.
- Если клиент готов купить — предложи связь с менеджером.
PROMPT,
                'is_published' => true,
                'published_at' => now(),
            ]);
        }

        $topics = [
            ['автомобили', 'авто,машин,машина,купить,нужна,kia,киа,bmw,audi,toyota,доставка,растаможка'],
            ['калькулятор', 'цена,стоимость,бюджет,расчёт,калькулятор'],
            ['консультация', 'подбор,помощь,консультация,под ключ'],
        ];
        foreach ($topics as [$topic, $kw]) {
            AiAllowedTopic::query()->updateOrCreate(
                ['profile_id' => $profile->id, 'topic' => $topic],
                ['keywords' => explode(',', $kw)]
            );
        }

        AiForbiddenTopic::query()->firstOrCreate(
            ['profile_id' => $profile->id, 'topic' => 'политика'],
            ['keywords' => ['политик', 'война', 'выборы'], 'action' => 'fallback']
        );

        foreach ([
            ['fallback', 'Спасибо за сообщение! Менеджер Pikhlak скоро подключится. Напишите марку авто и бюджет.'],
            ['blocked', 'Извините, с этим не помогу. Спросите про авто, доставку или расчёт стоимости.'],
            ['api_error', 'Техническая пауза. Напишите ещё раз или отправьте /start'],
            ['rate_limit', 'Сейчас лимит запросов к ИИ (Groq/Gemini). Попробуйте через 20–30 минут или напишите менеджеру — мы ответим вручную.'],
        ] as [$code, $text]) {
            AiResponseTemplate::query()->updateOrCreate(
                ['profile_id' => $profile->id, 'code' => $code],
                ['template' => $text, 'locale' => 'ru']
            );
        }

        AiWarmingScenario::query()->firstOrCreate(
            ['profile_id' => $profile->id, 'name' => 'Стандартный прогрев'],
            [
                'is_active' => true,
                'steps' => [
                    ['name' => 'Знакомство', 'instruction' => 'Поприветствуй, спроси какой класс авто интересует'],
                    ['name' => 'Бюджет', 'instruction' => 'Уточни бюджет и срок покупки'],
                    ['name' => 'Детали', 'instruction' => 'Уточни марку, год, комплектацию'],
                    ['name' => 'Контакт', 'instruction' => 'При высоком интересе предложи связь с менеджером'],
                ],
            ]
        );

        SettingHelper::saveBehavior($project->id, array_merge(config('ai.behavior_defaults'), [
            'use_project_data' => true,
            'warming_aggressiveness' => 'high',
        ]));
    }
}
