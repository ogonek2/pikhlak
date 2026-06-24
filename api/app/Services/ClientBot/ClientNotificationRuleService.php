<?php

namespace App\Services\ClientBot;

use App\Models\ClientNotificationRule;
use App\Models\Project;

class ClientNotificationRuleService
{
    /** @return array<int, int> */
    public function offsetsFor(Project $project, string $eventType): array
    {
        $rule = ClientNotificationRule::query()
            ->where('project_id', $project->id)
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->first();

        if ($rule && is_array($rule->offset_days) && $rule->offset_days !== []) {
            return array_values(array_map('intval', $rule->offset_days));
        }

        return config('client_bot.default_notification_offsets', [-5, -3, -1, 0, 1, 3]);
    }

    public function ensureDefaults(Project $project): void
    {
        foreach (array_keys(config('client_bot.event_types', [])) as $eventType) {
            ClientNotificationRule::query()->firstOrCreate(
                ['project_id' => $project->id, 'event_type' => $eventType],
                [
                    'offset_days' => config('client_bot.default_notification_offsets'),
                    'is_active' => true,
                ]
            );
        }
    }
}
