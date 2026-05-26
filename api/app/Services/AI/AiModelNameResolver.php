<?php

namespace App\Services\AI;

use App\Models\AiModel;

class AiModelNameResolver
{
    public static function resolve(?AiModel $model, ?string $provider = null): string
    {
        $provider ??= $model?->provider ?? config('ai.default_provider');
        $default = config("ai.providers.{$provider}.model");

        if (! $model?->model_name) {
            return $default;
        }

        $name = trim($model->model_name);

        if ($provider === 'gemini') {
            return strtolower(str_replace([' ', '_'], '-', $name));
        }

        return $name;
    }
}
