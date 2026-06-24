<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\ClientNotificationRule;
use App\Models\Project;
use App\Services\ClientBot\ClientNotificationRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientBotNotificationController extends Controller
{
    public function update(Request $request, ClientNotificationRuleService $rules): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $rules->ensureDefaults($project);

        $data = $request->validate([
            'rules' => ['required', 'array'],
            'rules.*.offset_days' => ['required', 'string'],
            'rules.*.is_active' => ['sometimes', 'boolean'],
        ]);

        foreach ($data['rules'] as $eventType => $ruleData) {
            $offsets = collect(preg_split('/[\s,;]+/', (string) $ruleData['offset_days']) ?: [])
                ->filter(fn ($v) => $v !== '' && is_numeric($v))
                ->map(fn ($v) => (int) $v)
                ->values()
                ->all();

            ClientNotificationRule::query()
                ->where('project_id', $project->id)
                ->where('event_type', $eventType)
                ->update([
                    'offset_days' => $offsets,
                    'is_active' => isset($ruleData['is_active']),
                ]);
        }

        return back()->with('success', 'Расписание уведомлений сохранено.');
    }
}
