<?php

namespace App\Services\AI;

use App\Models\AiFaqItem;
use App\Models\AiFaqMatch;

class FaqRetriever
{
    public function findBest(int $projectId, int $chatId, string $query): ?array
    {
        $queryLower = mb_strtolower($query);
        $best = null;
        $bestScore = 0.0;

        $items = AiFaqItem::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->get();

        foreach ($items as $item) {
            $score = $this->similarity($queryLower, mb_strtolower($item->question));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $item;
            }
        }

        $threshold = (float) (SettingHelper::behavior($projectId)['faq_match_threshold'] ?? 0.45);

        if (! $best || $bestScore < $threshold) {
            return null;
        }

        AiFaqMatch::query()->create([
            'faq_id' => $best->id,
            'chat_id' => $chatId,
            'score' => $bestScore,
        ]);

        return ['item' => $best, 'score' => $bestScore];
    }

    private function similarity(string $a, string $b): float
    {
        $wordsA = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $a)));
        $wordsB = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $b)));
        if ($wordsA === [] || $wordsB === []) {
            return 0;
        }
        $intersect = count(array_intersect($wordsA, $wordsB));

        return $intersect / max(count($wordsA), count($wordsB));
    }
}
