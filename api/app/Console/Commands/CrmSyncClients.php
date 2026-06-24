<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\ClientBot\Crm\CrmSyncService;
use Illuminate\Console\Command;

class CrmSyncClients extends Command
{
    protected $signature = 'pikhlak:crm-sync {--project= : Project slug}';

    protected $description = 'Синхронизировать клиентов и события из внешней CRM';

    public function handle(CrmSyncService $sync): int
    {
        $slug = $this->option('project') ?: config('pikhlak.default_project_slug', 'pikhlak');
        $project = Project::query()->where('slug', $slug)->first();

        if (! $project) {
            $this->error('Project not found: '.$slug);

            return self::FAILURE;
        }

        if (config('client_bot.crm.demo_mode', true) || ! config('client_bot.crm.base_url')) {
            $this->warn('CRM API не настроен (demo). Локальная БД — источник для бота и админки.');

            return self::SUCCESS;
        }

        $result = $sync->syncProject($project);
        $this->info("CRM synced: {$result['synced']}, failed: {$result['failed']}");

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
