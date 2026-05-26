<?php

namespace App\Services\CRM;

use App\Models\Chat;
use App\Models\Lead;
use App\Models\LeadScore;
use App\Models\LeadStatus;
use App\Models\Project;
use App\Services\Referral\ReferralTrackingService;

class LeadWarmingService
{
    public function __construct(
        private readonly ReferralTrackingService $referrals,
    ) {}

    public function ensureLead(Chat $chat, Project $project): Lead
    {
        $lead = Lead::query()
            ->where('project_id', $project->id)
            ->where('chat_id', $chat->id)
            ->first();

        if ($lead) {
            return $lead;
        }

        $status = LeadStatus::query()
            ->where('project_id', $project->id)
            ->where('code', 'new')
            ->first();

        $lead = Lead::query()->create([
            'project_id' => $project->id,
            'chat_id' => $chat->id,
            'telegram_user_id' => $chat->telegram_user_id,
            'status_id' => $status?->id,
            'warming_score' => 5,
            'source' => 'telegram_bot',
        ]);

        $this->referrals->attachToLead($lead, $chat, $project);

        return $lead->fresh();
    }

    public function scoreFromMessage(Lead $lead, string $userText, ?string $aiReply = null): int
    {
        $text = mb_strtolower($userText.' '.($aiReply ?? ''));
        $score = (int) $lead->warming_score;
        $factors = [];

        $signals = [
            'phone' => ['тел', 'номер', '+380', '+38', '0xx', 'звон', 'вайбер', 'telegram'],
            'budget' => ['бюджет', 'цена', 'usd', '$', 'дол', 'євро', 'грн', 'тыс', 'тис'],
            'car' => ['bmw', 'audi', 'mercedes', 'toyota', 'honda', 'ford', 'volkswagen', 'авто', 'машин', 'кросовер', 'седан'],
            'urgency' => ['срочно', 'швидко', 'скоро', 'сьогодні', 'завтра', 'купити', 'хочу'],
            'delivery' => ['достав', 'растамож', 'калькулятор', 'під ключ'],
        ];

        $weights = ['phone' => 25, 'budget' => 20, 'car' => 15, 'urgency' => 15, 'delivery' => 10];

        foreach ($signals as $key => $words) {
            foreach ($words as $word) {
                if (str_contains($text, $word)) {
                    $score += $weights[$key];
                    $factors[$key] = ($factors[$key] ?? 0) + $weights[$key];
                    break;
                }
            }
        }

        $score = min(100, max(0, $score));

        $lead->update([
            'warming_score' => $score,
            'last_contacted_at' => now(),
        ]);

        LeadScore::query()->create([
            'lead_id' => $lead->id,
            'score' => $score,
            'factors' => $factors,
            'calculated_at' => now(),
        ]);

        if ($score >= 70) {
            $qualified = LeadStatus::query()
                ->where('project_id', $lead->project_id)
                ->where('code', 'qualified')
                ->first();
            if ($qualified && $lead->status_id !== $qualified->id) {
                $lead->update(['status_id' => $qualified->id]);
            }
        }

        return $score;
    }

    public function resolveWarmingStep(Lead $lead, int $projectId): ?string
    {
        $scenario = \App\Models\AiWarmingScenario::query()
            ->whereHas('profile', fn ($q) => $q->where('project_id', $projectId)->where('is_default', true))
            ->where('is_active', true)
            ->first();

        if (! $scenario || empty($scenario->steps)) {
            return null;
        }

        $steps = $scenario->steps;
        $index = min(count($steps) - 1, (int) floor($lead->warming_score / (100 / max(1, count($steps)))));

        return $steps[$index]['instruction'] ?? $steps[$index]['name'] ?? null;
    }
}
