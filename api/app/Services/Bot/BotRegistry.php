<?php

namespace App\Services\Bot;

use App\Models\Bot;
use App\Models\Project;
use Illuminate\Support\Str;

class BotRegistry
{
    public const TYPE_WARMING = 'warming';

    public const TYPE_CLIENT = 'client';

    public function forProject(Project $project, string $type = self::TYPE_WARMING): Bot
    {
        $bot = Bot::query()
            ->where('project_id', $project->id)
            ->where('type', $type)
            ->first();

        if ($bot) {
            return $bot;
        }

        return Bot::query()->create([
            'project_id' => $project->id,
            'uuid' => (string) Str::uuid(),
            'name' => $type === self::TYPE_CLIENT ? 'Pikhlak Client Bot' : 'Pikhlak Warming Bot',
            'type' => $type,
            'mode' => 'webhook',
            'webhook_secret' => $type === self::TYPE_CLIENT
                ? 'pikhlak-client-webhook-secret'
                : 'pikhlak-webhook-secret-dev',
            'api_key_hash' => hash('sha256', config('pikhlak.bot_hmac_secret', 'dev')),
            'config' => ['language' => 'uk'],
            'is_active' => true,
        ]);
    }

    public function warming(Project $project): Bot
    {
        return $this->forProject($project, self::TYPE_WARMING);
    }

    public function client(Project $project): Bot
    {
        return $this->forProject($project, self::TYPE_CLIENT);
    }
}
