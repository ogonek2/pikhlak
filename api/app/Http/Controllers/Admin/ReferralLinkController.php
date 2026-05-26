<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Car;
use App\Models\Manager;
use App\Models\Project;
use App\Models\ReferralCampaign;
use App\Models\ReferralEvent;
use App\Models\ReferralLink;
use App\Services\Referral\ReferralLinkBuilder;
use App\Services\Referral\ReferralTrackingService;
use App\Services\Telegram\TelegramBotProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralLinkController extends Controller
{
    public function __construct(
        private readonly ReferralLinkBuilder $builder,
        private readonly ReferralTrackingService $tracking,
        private readonly TelegramBotProfileService $botProfile,
    ) {}

    public function index(Request $request): View
    {
        $project = $this->project($request);

        $query = ReferralLink::query()
            ->where('project_id', $project->id)
            ->with(['car', 'campaign', 'manager.user']);

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }
        if ($channel = $request->string('channel')->toString()) {
            $query->where('channel', $channel);
        }
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $links = $query->orderByDesc('updated_at')->paginate(25)->withQueryString();
        $stats = $this->tracking->statsForProject($project->id);
        $bot = $this->projectBot($project);

        return view('admin.referrals.index', [
            'links' => $links,
            'stats' => $stats,
            'bot' => $bot,
            'botUsername' => $this->botProfile->username($bot),
            'telegramReady' => (bool) $this->botProfile->username($bot),
            'channels' => config('referrals.channels', []),
            'types' => config('referrals.types', []),
            'filterType' => $type ?? '',
            'filterChannel' => $channel ?? '',
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.referrals.form', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $this->project($request);
        $bot = $this->projectBot($project);
        $data = $this->validated($request, $project);
        $data['project_id'] = $project->id;
        $data['bot_id'] = $bot->id;
        $data['code'] = $this->builder->generateCode($data['code'] ?? null, $project->id, $bot->id);
        $data['settings'] = $this->mergeSettings($request);
        $data = $this->applyUtmDefaults($data);

        ReferralLink::query()->create($data);

        return redirect()->route('admin.referrals.index')->with('success', 'Реферальная ссылка создана.');
    }

    public function edit(Request $request, ReferralLink $referralLink): View
    {
        $this->ensureProjectLink($request, $referralLink);

        return view('admin.referrals.form', array_merge($this->formData($request), [
            'link' => $referralLink->load(['car', 'campaign']),
        ]));
    }

    public function update(Request $request, ReferralLink $referralLink): RedirectResponse
    {
        $this->ensureProjectLink($request, $referralLink);
        $project = $this->project($request);
        $data = $this->validated($request, $project, $referralLink);

        $bot = $this->projectBot($project);
        if (! empty($data['code']) && $data['code'] !== $referralLink->code) {
            $data['code'] = $this->builder->generateCode($data['code'], $project->id, $bot->id);
        } else {
            unset($data['code']);
        }

        $data['settings'] = $this->mergeSettings($request);
        $data = $this->applyUtmDefaults($data);
        $referralLink->update($data);

        return redirect()->route('admin.referrals.index')->with('success', 'Ссылка обновлена.');
    }

    public function destroy(Request $request, ReferralLink $referralLink): RedirectResponse
    {
        $this->ensureProjectLink($request, $referralLink);
        $referralLink->delete();

        return redirect()->route('admin.referrals.index')->with('success', 'Ссылка удалена.');
    }

    public function show(Request $request, ReferralLink $referralLink): View
    {
        $this->ensureProjectLink($request, $referralLink);
        $referralLink->load(['car', 'campaign', 'manager.user']);

        $events = ReferralEvent::query()
            ->where('link_id', $referralLink->id)
            ->with('lead')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $leads = $referralLink->leads()
            ->with('status')
            ->latest()
            ->limit(50)
            ->get();

        $bot = $this->projectBot($referralLink->project);
        $telegramUrl = $this->builder->telegramUrl($bot, $referralLink);
        $botUsername = $this->botProfile->username($bot);

        return view('admin.referrals.show', compact('referralLink', 'events', 'leads', 'telegramUrl', 'bot', 'botUsername'));
    }

    private function formData(Request $request): array
    {
        $project = $this->project($request);

        return [
            'link' => null,
            'cars' => Car::query()->where('project_id', $project->id)->where('status', 'published')->orderBy('make')->get(),
            'campaigns' => ReferralCampaign::query()->where('project_id', $project->id)->where('is_active', true)->orderBy('name')->get(),
            'managers' => Manager::query()->where('project_id', $project->id)->with('user')->get(),
            'channels' => config('referrals.channels', []),
            'types' => config('referrals.types', []),
            'defaultSettings' => config('referrals.default_settings', []),
            'bot' => $this->projectBot($project),
            'botUsername' => $this->botProfile->username($this->projectBot($project)),
        ];
    }

    private function projectBot(Project $project): Bot
    {
        $bot = Bot::query()->where('project_id', $project->id)->where('is_active', true)->first()
            ?? Bot::query()->where('project_id', $project->id)->first();

        if (! $bot) {
            abort(422, 'Бот Pikhlak для проекта не найден.');
        }

        if (! $this->botProfile->username($bot) && $bot->telegram_token) {
            try {
                $this->botProfile->sync($bot);
                $bot->refresh();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $bot;
    }

    private function validated(Request $request, Project $project, ?ReferralLink $existing = null): array
    {
        $types = array_keys(config('referrals.types', []));
        $channels = array_keys(config('referrals.channels', []));

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:64'],
            'type' => ['required', 'in:'.implode(',', $types)],
            'channel' => ['nullable', 'string', 'max:50'],
            'campaign_id' => ['nullable', 'exists:referral_campaigns,id'],
            'car_id' => ['nullable', 'exists:cars,id'],
            'partner_name' => ['nullable', 'string', 'max:120'],
            'partner_contact' => ['nullable', 'string', 'max:120'],
            'partner_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'manager_id' => ['nullable', 'exists:managers,id'],
            'utm_source' => ['nullable', 'string', 'max:80'],
            'utm_medium' => ['nullable', 'string', 'max:80'],
            'utm_campaign' => ['nullable', 'string', 'max:80'],
            'utm_content' => ['nullable', 'string', 'max:80'],
            'utm_term' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'landing_message' => ['nullable', 'string', 'max:4000'],
            'expires_at' => ['nullable', 'date'],
            'max_starts' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
            'channel' => $request->filled('channel') ? $request->string('channel')->toString() : null,
        ];
    }

    private function mergeSettings(Request $request): array
    {
        $defaults = config('referrals.default_settings', []);

        return array_merge($defaults, array_filter([
            'auto_show_car' => $request->boolean('setting_auto_show_car'),
            'pin_car_in_context' => $request->boolean('setting_pin_car_in_context'),
            'assign_warming_bonus' => (int) $request->input('setting_assign_warming_bonus', 5),
            'notify_new_lead' => $request->boolean('setting_notify_new_lead'),
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string) $request->input('setting_tags', ''))))),
        ]));
    }

    private function project(Request $request): Project
    {
        return $request->attributes->get('project');
    }

    private function ensureProjectLink(Request $request, ReferralLink $link): void
    {
        if ($link->project_id !== $this->project($request)->id) {
            abort(404);
        }
    }

    private function applyUtmDefaults(array $data): array
    {
        if (empty($data['utm_source']) && ! empty($data['channel'])) {
            $data['utm_source'] = $data['channel'];
        }
        if (empty($data['utm_medium'])) {
            $data['utm_medium'] = 'telegram';
        }

        return $data;
    }
}
