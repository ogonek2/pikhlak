<?php

namespace App\Services\AI;

use App\Models\Setting;

class SettingHelper
{
    public const BEHAVIOR_KEY = 'ai.behavior';

    public static function behavior(int $projectId): array
    {
        return array_replace_recursive(
            config('ai.behavior_defaults'),
            Setting::getValue($projectId, self::BEHAVIOR_KEY, []) ?? []
        );
    }

    public static function saveBehavior(int $projectId, array $behavior): void
    {
        Setting::setValue($projectId, self::BEHAVIOR_KEY, $behavior);
    }
}
