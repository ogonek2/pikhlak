<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\AiProfile;
use App\Models\Project;
use Illuminate\Console\Command;

class SyncAiProvider extends Command
{
    protected $signature = 'pikhlak:sync-ai-provider';

    protected $description = 'Sync AI profile model from AI_PROVIDER in .env';

    public function handle(): int
    {
        $provider = config('ai.default_provider', 'groq');
        $config = config("ai.providers.{$provider}");
        $modelName = $config['model'] ?? 'default';

        $aiModel = AiModel::query()->updateOrCreate(
            ['provider' => $provider, 'model_name' => $modelName],
            ['is_active' => true]
        );

        $project = Project::query()->where('slug', config('pikhlak.default_project_slug', 'pikhlak'))->first();
        if ($project) {
            AiProfile::query()
                ->where('project_id', $project->id)
                ->where('is_default', true)
                ->update(['model_id' => $aiModel->id]);
        }

        $this->info("AI provider synced: {$provider} / {$modelName}");

        return self::SUCCESS;
    }
}
