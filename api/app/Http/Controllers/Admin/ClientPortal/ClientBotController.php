<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\ClientNotificationRule;
use App\Models\CrmSyncLog;
use App\Models\Project;
use App\Services\Bot\BotRegistry;
use App\Services\ClientBot\ClientManagerRequestService;
use App\Services\ClientBot\ClientNotificationRuleService;
use App\Services\Telegram\TelegramBotProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientBotController extends Controller
{
    public function __construct(
        private readonly BotRegistry $bots,
        private readonly TelegramBotProfileService $telegram,
        private readonly ClientNotificationRuleService $notificationRules,
        private readonly ClientManagerRequestService $managerRequests,
    ) {}

    public function show(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $bot = $this->bots->client($project);
        $this->notificationRules->ensureDefaults($project);

        return view('admin.client.bot.show', [
            'bot' => $bot,
            'project' => $project,
            'username' => $this->telegram->username($bot),
            'notificationRules' => ClientNotificationRule::query()
                ->where('project_id', $project->id)
                ->orderBy('event_type')
                ->get(),
            'eventTypes' => config('client_bot.event_types', []),
            'crmFields' => config('client_bot.crm_snapshot_fields', []),
            'lastCrmSync' => CrmSyncLog::query()
                ->where('project_id', $project->id)
                ->latest('id')
                ->first(),
            'crmConfigured' => ! config('client_bot.crm.demo_mode', true) && (bool) config('client_bot.crm.base_url'),
            'pendingManagerRequests' => $this->managerRequests->pendingCount($project->id),
            'latestManagerRequests' => $this->managerRequests->latestPending($project->id, 5),
            'managerSettings' => $this->managerRequests->settingsForBot($bot),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $bot = $this->bots->client($project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'telegram_token' => ['nullable', 'string', 'max:120'],
            'mode' => ['required', 'in:webhook,polling'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');
        $token = trim((string) $request->input('telegram_token', ''));
        unset($data['telegram_token']);

        $bot->fill($data);
        if ($token !== '') {
            $bot->telegram_token = $token;
        }
        $bot->save();

        if ($bot->telegram_token) {
            try {
                $this->telegram->sync($bot->fresh());
            } catch (\Throwable $e) {
                return back()->with('error', 'Бот сохранён, но Telegram не подтвердил токен: '.$e->getMessage());
            }
        }

        return back()->with('success', 'Клиентский бот сохранён.');
    }
}
