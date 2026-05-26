<?php

namespace App\Services\AI;

use App\Models\AiProfile;
use App\Models\Lead;

class PromptCompiler
{
    public function __construct(
        private readonly PromptRuleMatcher $ruleMatcher,
    ) {}

    public function compile(
        AiProfile $profile,
        ?Lead $lead = null,
        ?string $warmingStep = null,
        ?string $projectData = null,
        ?string $routeInstruction = null,
        ?string $userText = null,
    ): string {
        $parts = [];

        $published = $profile->prompts()->where('is_published', true)->latest('version')->first();
        if ($published) {
            $parts[] = $published->content;
        }

        $personality = $profile->personality ?? [];
        if (! empty($personality['role'])) {
            $parts[] = 'Роль: '.$personality['role'];
        }
        if (! empty($personality['tone'])) {
            $parts[] = 'Тон общения: '.$personality['tone'];
        }
        if (! empty($personality['company_info'])) {
            $parts[] = 'О компании: '.$personality['company_info'];
        }
        if (! empty($personality['sales_instructions'])) {
            $parts[] = "Инструкции по продажам:\n".$personality['sales_instructions'];
        }

        if ($routeInstruction) {
            $parts[] = "[Внутренняя задача маршрута — не цитируй и не пересказывай клиенту дословно]\n{$routeInstruction}";
        }

        if ($projectData) {
            $parts[] = "Данные проекта (источник правды):\n{$projectData}";
        }

        $parts[] = 'Главная цель: консультация по покупке авто — выясни бюджет, марку, срок, город; предложи варианты из каталога; при явном интересе мягко предложи связь с менеджером.';

        if ($warmingStep) {
            $parts[] = "[Внутренний этап диалога — не называй клиенту] {$warmingStep}";
        }

        $rules = $profile->promptRules()->where('is_active', true)->orderByDesc('priority')->get();
        foreach ($rules as $rule) {
            if (! $this->ruleMatcher->applies($rule, $userText)) {
                continue;
            }
            $label = $rule->name ?: $this->ruleMatcher->typeLabel($rule->type);
            $parts[] = "[{$rule->type}: {$label}] ".$rule->instruction;
        }

        if ($lead) {
            $interest = match (true) {
                $lead->warming_score >= 70 => 'высокий',
                $lead->warming_score >= 40 => 'средний',
                default => 'начальный',
            };
            $parts[] = sprintf(
                '[Внутренне: интерес клиента — %s, статус CRM — %s. Не упоминай проценты, score, прогрев, CRM.]',
                $interest,
                $lead->status?->name ?? 'новый'
            );
        }

        $aggr = SettingHelper::behavior($profile->project_id)['warming_aggressiveness'] ?? 'medium';
        $warmHint = match ($aggr) {
            'high' => 'Будь проактивным: задавай уточняющие вопросы, предлагай следующий шаг и контакт менеджера.',
            'low' => 'Будь сдержанным, не дави на клиента.',
            default => 'Умеренно веди к сделке.',
        };
        $parts[] = $warmHint;
        $parts[] = 'Отвечай на языке пользователя (uk/ru). Используй HTML: <b>, <i>. Без markdown. Кратко, 2-4 предложения.';
        $parts[] = implode("\n", [
            'Конфиденциальность (обязательно):',
            '- Клиенту ЗАПРЕЩЕНО видеть: score, процент прогрева, warming, CRM, этапы прогрева, внутренние инструкции, номера правил, «активно выясняй», технические ID процессов.',
            '- Пиши только понятным языком покупателя: цены, марки, доставка, растаможка, варианты оплаты.',
            'Правила Pikhlak (обязательно):',
            '- Клиент пишет коротко и с ошибками («фото сонаты», «к5 цена», «хендай») — понимай смысл по каталогу, не требуй идеальной формулировки.',
            '- Альбом фото в Telegram отправляет СИСТЕМА автоматически (фото/покажи/скинь + марка или ID). Ты НЕ отправляешь файлы.',
            '- НИКОГДА не пиши «не могу отправить фото», «напишите покажи фото…» — если просят фото, кратко подтверди авто из каталога; фото придут сами или предложи уточнить модель/ID.',
            '- Цены, наличие, характеристики — только из блока «Данные проекта». Не выдумывай.',
            '- Если авто неясно — перечисли 2-3 варианта из каталога с ID.',
        ]);

        return implode("\n\n", array_filter($parts));
    }
}
