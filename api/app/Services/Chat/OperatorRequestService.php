<?php

namespace App\Services\Chat;

use App\Models\Chat;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Project;
use App\Services\AI\SettingHelper;
use App\Services\Referral\ReferralTrackingService;

class OperatorRequestService
{
    public function __construct(
        private readonly ChatStateService $chatState,
        private readonly ReferralTrackingService $referrals,
    ) {}

    public function detectsOperatorRequest(string $text): bool
    {
        $lower = mb_strtolower($text);

        foreach ([
            'оператор', 'менеджер', 'человек', 'живой', 'позвоните', 'перезвоните',
            'связаться с', 'свяжите', 'переключите на', 'хочу поговорить',
            'позвать менеджера', 'нужен менеджер', 'живого человека',
        ] as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(manager|operator|human)\b/u', $lower);
    }

    public function flag(Chat $chat, Project $project, ?Lead $lead = null): Lead
    {
        $lead ??= Lead::query()->firstOrCreate(
            ['project_id' => $project->id, 'chat_id' => $chat->id],
            [
                'telegram_user_id' => $chat->telegram_user_id,
                'warming_score' => 10,
                'source' => 'telegram_bot',
            ]
        );

        if (! $lead->operator_requested_at) {
            $lead->operator_requested_at = now();
        }
        $lead->operator_handled_at = null;

        $operatorStatus = LeadStatus::query()
            ->where('project_id', $project->id)
            ->where('code', 'operator')
            ->first();
        if ($operatorStatus) {
            $lead->status_id = $operatorStatus->id;
        }

        $lead->save();

        $this->referrals->attachToLead($lead, $chat, $project);

        if (SettingHelper::behavior($project->id)['disable_ai_on_operator_request'] ?? true) {
            $this->chatState->setMode($chat, ChatStateService::MODE_HUMAN);
        }

        return $lead;
    }

    public function markHandled(Lead $lead, Chat $chat): void
    {
        $lead->update(['operator_handled_at' => now()]);
    }

    public function pendingCount(int $projectId): int
    {
        return Lead::query()
            ->where('project_id', $projectId)
            ->whereNotNull('operator_requested_at')
            ->whereNull('operator_handled_at')
            ->count();
    }
}
