<?php

namespace App\Services\AI;

use App\Models\AiPromptRule;

class PromptRuleMatcher
{
    public function applies(AiPromptRule $rule, ?string $userText = null): bool
    {
        $condition = $rule->condition ?? [];

        if ($condition['always'] ?? false) {
            return true;
        }

        $keywords = $condition['keywords'] ?? [];
        if (! is_array($keywords)) {
            $keywords = [];
        }

        if ($keywords === [] && $condition === []) {
            return true;
        }

        if ($keywords === []) {
            return false;
        }

        if ($userText === null || trim($userText) === '') {
            return false;
        }

        $lower = mb_strtolower($userText);
        foreach ($keywords as $keyword) {
            $kw = mb_strtolower(trim((string) $keyword));
            if ($kw !== '' && str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }

    public function typeLabel(string $type): string
    {
        return config('ai.rule_types.'.$type, $type);
    }
}
