<?php

namespace App\Services\Referral;

use App\Models\Bot;
use App\Models\Chat;
use App\Models\Lead;
use App\Models\Project;
use App\Models\ReferralAttribution;
use App\Models\ReferralEvent;
use App\Models\ReferralLink;
use App\Services\Telegram\TelegramBotProfileService;

class ReferralTrackingService
{
    public function __construct(
        private readonly ReferralLinkBuilder $builder,
        private readonly TelegramBotProfileService $botProfile,
    ) {}

    public function parseStartPayload(string $text): ?string
    {
        $text = trim($text);
        if (preg_match('/^\/start(?:@\w+)?\s+(.+)$/iu', $text, $m)) {
            return $this->builder->sanitizeCode(trim($m[1]));
        }

        if (preg_match('/^start\s+(.+)$/iu', $text, $m)) {
            return $this->builder->sanitizeCode(trim($m[1]));
        }

        return null;
    }

    public function resolveLink(Bot $bot, string $code): ?ReferralLink
    {
        if (! $this->botProfile->username($bot)) {
            return null;
        }

        $link = ReferralLink::query()
            ->where('bot_id', $bot->id)
            ->where('project_id', $bot->project_id)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $link || ! $link->isUsable()) {
            return null;
        }

        return $link;
    }

    public function handleStartCommand(Bot $bot, Chat $chat, string $rawText): ?ReferralLink
    {
        if (! $this->botProfile->matchesStartCommand($bot, $rawText)) {
            return null;
        }

        $payload = $this->parseStartPayload($rawText);
        if (! $payload) {
            return null;
        }

        $link = $this->resolveLink($bot, $payload);
        if (! $link) {
            return null;
        }

        $this->recordStart($link, $chat, [
            'payload' => $payload,
            'bot_id' => $bot->id,
            'bot_username' => $this->botProfile->username($bot),
        ]);

        return $link;
    }

    public function recordStart(ReferralLink $link, Chat $chat, array $meta = []): void
    {
        $link->increment('clicks_count');
        $link->increment('starts_count');

        $this->logEvent($link, 'start', null, array_merge($meta, [
            'chat_id' => $chat->id,
            'telegram_chat_id' => $chat->telegram_chat_id,
        ]));

        $state = $chat->state ?? [];
        $state['referral_link_id'] = $link->id;
        $state['referral_code'] = $link->code;
        $state['referral_at'] = now()->toIso8601String();

        if ($link->car_id) {
            $state['referral_car_id'] = $link->car_id;
        }

        $chat->update(['state' => $state]);
    }

    public function pendingLinkId(Chat $chat): ?int
    {
        return isset($chat->state['referral_link_id'])
            ? (int) $chat->state['referral_link_id']
            : null;
    }

    public function attachToLead(Lead $lead, Chat $chat, Project $project): void
    {
        $linkId = $this->pendingLinkId($chat);
        if (! $linkId) {
            return;
        }

        $link = ReferralLink::query()
            ->where('project_id', $project->id)
            ->where('bot_id', $chat->bot_id)
            ->find($linkId);

        if (! $link) {
            return;
        }

        if ($lead->referral_link_id) {
            ReferralAttribution::query()
                ->where('lead_id', $lead->id)
                ->update(['last_touch_at' => now()]);

            return;
        }

        $settings = $link->settings ?? [];
        $warmingBonus = (int) ($settings['assign_warming_bonus'] ?? 5);

        $metadata = $lead->metadata ?? [];
        $metadata['referral'] = [
            'link_id' => $link->id,
            'code' => $link->code,
            'type' => $link->type,
            'channel' => $link->channel,
            'channel_label' => $link->channelLabel(),
            'utm' => $this->utmPayload($link),
            'partner_name' => $link->partner_name,
        ];

        $updates = [
            'referral_link_id' => $link->id,
            'source' => $this->buildSourceLabel($link),
            'metadata' => $metadata,
            'warming_score' => min(100, (int) $lead->warming_score + $warmingBonus),
        ];

        if ($link->car_id && ! $lead->car_interest_id) {
            $updates['car_interest_id'] = $link->car_id;
        }

        if ($link->manager_id && ! $lead->assigned_manager_id) {
            $updates['assigned_manager_id'] = $link->manager_id;
        }

        $lead->update($updates);

        ReferralAttribution::query()->updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'link_id' => $link->id,
                'first_touch_at' => now(),
                'last_touch_at' => now(),
            ]
        );

        $link->increment('leads_count');
        $this->logEvent($link, 'lead_created', $lead->id, ['chat_id' => $chat->id]);
    }

    public function recordConversion(Lead $lead): void
    {
        if (! $lead->referral_link_id) {
            return;
        }

        $link = ReferralLink::query()->find($lead->referral_link_id);
        if (! $link) {
            return;
        }

        $already = ReferralEvent::query()
            ->where('link_id', $link->id)
            ->where('lead_id', $lead->id)
            ->where('event_type', 'converted')
            ->exists();

        if ($already) {
            return;
        }

        $link->increment('conversions_count');
        $this->logEvent($link, 'converted', $lead->id);
    }

    public function statsForProject(int $projectId): array
    {
        $links = ReferralLink::query()->where('project_id', $projectId);

        $totals = (clone $links)->selectRaw('
            COALESCE(SUM(starts_count),0) as starts,
            COALESCE(SUM(leads_count),0) as leads,
            COALESCE(SUM(conversions_count),0) as conversions,
            COALESCE(SUM(clicks_count),0) as clicks
        ')->first();

        $byChannel = ReferralLink::query()
            ->where('project_id', $projectId)
            ->selectRaw('channel, SUM(starts_count) as starts, SUM(leads_count) as leads, SUM(conversions_count) as conversions')
            ->groupBy('channel')
            ->orderByDesc('starts')
            ->get();

        $byType = ReferralLink::query()
            ->where('project_id', $projectId)
            ->selectRaw('type, SUM(starts_count) as starts, SUM(leads_count) as leads')
            ->groupBy('type')
            ->get();

        $starts = (int) ($totals->starts ?? 0);
        $leads = (int) ($totals->leads ?? 0);

        return [
            'starts' => $starts,
            'clicks' => (int) ($totals->clicks ?? 0),
            'leads' => $leads,
            'conversions' => (int) ($totals->conversions ?? 0),
            'lead_rate' => $starts > 0 ? round(($leads / $starts) * 100, 1) : 0,
            'by_channel' => $byChannel,
            'by_type' => $byType,
            'active_links' => ReferralLink::query()->where('project_id', $projectId)->where('is_active', true)->count(),
        ];
    }

    private function buildSourceLabel(ReferralLink $link): string
    {
        $parts = ['ref', $link->type];
        if ($link->channel) {
            $parts[] = $link->channel;
        }
        $parts[] = $link->code;

        return implode(':', $parts);
    }

    private function utmPayload(ReferralLink $link): array
    {
        return array_filter([
            'utm_source' => $link->utm_source ?? $link->channel,
            'utm_medium' => $link->utm_medium ?? 'telegram',
            'utm_campaign' => $link->utm_campaign ?? $link->campaign?->code,
            'utm_content' => $link->utm_content,
            'utm_term' => $link->utm_term,
        ]);
    }

    private function logEvent(ReferralLink $link, string $type, ?int $leadId = null, array $meta = []): void
    {
        ReferralEvent::query()->create([
            'link_id' => $link->id,
            'event_type' => $type,
            'lead_id' => $leadId,
            'meta' => $meta ?: null,
        ]);
    }
}
