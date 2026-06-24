<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\ClientBot\ClientNotificationPlanner;
use App\Services\ClientBot\ClientNotificationRuleService;
use App\Services\ClientBot\ClientNotificationSender;
use Illuminate\Console\Command;

class ClientBotNotify extends Command
{
    protected $signature = 'pikhlak:client-bot-notify {--project= : Project slug}';

    protected $description = 'Отправить клиентам напоминания о платежах, ТО и страховке';

    public function handle(
        ClientNotificationPlanner $planner,
        ClientNotificationSender $sender,
        ClientNotificationRuleService $rules,
    ): int {
        $slug = $this->option('project') ?: config('pikhlak.default_project_slug', 'pikhlak');
        $project = Project::query()->where('slug', $slug)->first();

        if (! $project) {
            $this->error('Project not found: '.$slug);

            return self::FAILURE;
        }

        $rules->ensureDefaults($project);
        $due = $planner->dueNotifications($project);
        $sent = 0;

        foreach ($due as $item) {
            if ($sender->send($item)) {
                $sent++;
            }
        }

        $this->info("Notifications sent: {$sent} / ".$due->count()." for {$project->name}");

        return self::SUCCESS;
    }
}
