<?php

namespace Database\Seeders;

use App\Models\AiProfile;
use App\Models\AiPromptRule;
use App\Models\Project;
use Illuminate\Database\Seeder;

class AiPromptRulesSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->where('slug', config('pikhlak.default_project_slug', 'pikhlak'))->first();
        if (! $project) {
            return;
        }

        $profile = AiProfile::query()->where('project_id', $project->id)->where('is_default', true)->first();
        if (! $profile) {
            return;
        }

        foreach (config('ai.rule_presets', []) as $preset) {
            AiPromptRule::query()->updateOrCreate(
                [
                    'profile_id' => $profile->id,
                    'name' => $preset['name'],
                ],
                [
                    'type' => $preset['type'],
                    'priority' => (int) ($preset['priority'] ?? 50),
                    'instruction' => $preset['instruction'],
                    'is_active' => true,
                    'condition' => [
                        'always' => (bool) ($preset['always'] ?? false),
                        'keywords' => array_values(array_filter(array_map('trim', explode(',', $preset['keywords'] ?? ''))))),
                    ],
                ]
            );
        }
    }
}
