<?php

namespace App\Services\AI;

/**
 * Убирает из ответов бота внутренние инструкции, CRM-метрики и админскую лексику.
 */
class ClientReplySanitizer
{
    /** @var list<string> */
    private const LEAK_PATTERNS = [
        '/\bscore\s*[><=]\s*\d+/iu',
        '/\bwarming_score\b/iu',
        '/\bпрогрев\s+лида\b/iu',
        '/\bэтап\s+прогрева\b/iu',
        '/📋/u',
        '/Автоответ по теме запроса/iu',
        '/Активно выясняй/ui',
        '/мягко\s+проси\s+контакт\s+при/ui',
        '/активные правила для этого сообщения/iu',
        '/\[(company_fact|constraint|correction|instruction|example):/iu',
        '/\bCRM:\s*warming/iu',
        '/Задача маршрута:/iu',
        '/Только для ИИ/ui',
        '/из каталога по ID/ui',
        '/предлагай авто из каталога по ID/ui',
        '/\bпереплат[аы].*\d+\s*%/iu',
        '/\boverpayment_rate\b/iu',
        '/\bcalculation_snapshot\b/iu',
    ];

    /** @var list<string> */
    private const CLIENT_LEAK_PATTERNS = [
        '/каталог\s+авто/ui',
        '/подбор\s+автомобил/ui',
        '/доставк[аи]\s+из/ui',
        '/warming_score/ui',
    ];

    public function sanitize(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if ($this->looksLikeInternalLeak($text)) {
            return $this->safeFallback();
        }

        $cleaned = $text;
        foreach (self::LEAK_PATTERNS as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned) ?? $cleaned;
        }

        $cleaned = preg_replace('/\n{3,}/', "\n\n", trim($cleaned)) ?? $cleaned;

        if ($cleaned === '' || $this->looksLikeInternalLeak($cleaned)) {
            return $this->safeFallback();
        }

        return $cleaned;
    }

    private function looksLikeInternalLeak(string $text): bool
    {
        foreach (self::LEAK_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    public function safeFallback(): string
    {
        return 'Спасибо за сообщение! Подскажите, пожалуйста, какой автомобиль вас интересует и в каком бюджете — помогу с подбором и доставкой. Или напишите менеджеру через /start.';
    }

    public function sanitizeForClient(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if ($this->looksLikeInternalLeak($text) || $this->looksLikeClientLeak($text)) {
            return $this->clientSafeFallback();
        }

        $cleaned = $text;
        foreach (array_merge(self::LEAK_PATTERNS, self::CLIENT_LEAK_PATTERNS) as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned) ?? $cleaned;
        }

        $cleaned = preg_replace('/\n{3,}/', "\n\n", trim($cleaned)) ?? $cleaned;

        if ($cleaned === '' || $this->looksLikeInternalLeak($cleaned) || $this->looksLikeClientLeak($cleaned)) {
            return $this->clientSafeFallback();
        }

        return $cleaned;
    }

    public function clientSafeFallback(): string
    {
        return 'Не нашёл точного ответа в ваших данных по договору. Нажмите кнопку «Менеджер» или уточните вопрос — например, про остаток, платёж или ТО.';
    }

    private function looksLikeClientLeak(string $text): bool
    {
        foreach (self::CLIENT_LEAK_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }
}
