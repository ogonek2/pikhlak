<?php

namespace App\Services\AI;

use App\Models\AiRoute;

class AiRouteResolver
{
    public function resolve(int $projectId, string $userText): ?AiRoute
    {
        $lower = mb_strtolower(trim($userText));

        $routes = AiRoute::query()
            ->with('model')
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->where('slug', '!=', 'default')
            ->orderByDesc('priority')
            ->get();

        foreach ($routes as $route) {
            if ($this->matches($lower, $route->intent_keywords ?? [])) {
                return $route;
            }
        }

        return AiRoute::query()
            ->where('project_id', $projectId)
            ->where('slug', 'default')
            ->where('is_active', true)
            ->first();
    }

    private function matches(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            $kw = mb_strtolower(trim((string) $keyword));
            if ($kw !== '' && str_contains($text, $kw)) {
                return true;
            }
        }

        return false;
    }
}
