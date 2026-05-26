<?php

namespace App\Services\AI;

use App\Models\AiProfile;

class GuardrailService
{
    public function check(AiProfile $profile, string $text, bool $strictAllowed = false): array
    {
        $lower = mb_strtolower($text);

        foreach ($profile->forbiddenTopics as $topic) {
            if ($this->matches($lower, $topic->topic, $topic->keywords)) {
                return [
                    'blocked' => true,
                    'action' => $topic->action,
                    'topic' => $topic->topic,
                    'reason' => 'forbidden_topic',
                ];
            }
        }

        if (! $strictAllowed) {
            return ['blocked' => false];
        }

        $allowed = $profile->allowedTopics ?? collect();
        if ($allowed->count() === 0) {
            return ['blocked' => false];
        }

        foreach ($allowed as $topic) {
            if ($this->matches($lower, $topic->topic, $topic->keywords, true)) {
                return ['blocked' => false];
            }
        }

        return [
            'blocked' => true,
            'action' => 'fallback',
            'topic' => 'off_topic',
            'reason' => 'not_in_allowed_topics',
        ];
    }

    private function matches(string $text, string $topic, ?array $keywords, bool $expandStems = false): bool
    {
        $needles = [mb_strtolower($topic)];
        foreach ($keywords ?? [] as $keyword) {
            $kw = mb_strtolower(trim((string) $keyword));
            if ($kw !== '') {
                $needles[] = $kw;
            }
        }

        if ($expandStems) {
            $needles = array_merge($needles, [
                'машин', 'авто', 'куп', 'нужн', 'хочу', 'интерес', 'достав', 'бюджет',
                'kia', 'киа', 'bmw', 'audi', 'toyota', 'mercedes', 'honda', 'hyundai',
            ]);
        }

        foreach (array_unique($needles) as $needle) {
            if ($needle !== '' && str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
