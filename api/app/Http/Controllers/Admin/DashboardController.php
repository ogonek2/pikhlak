<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFaqItem;
use App\Models\Chat;
use App\Models\Lead;
use App\Models\Project;
use App\Services\Bot\BotRegistry;
use App\Services\Chat\OperatorRequestService;
use App\Services\Referral\ReferralTrackingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly OperatorRequestService $operators,
        private readonly ReferralTrackingService $referrals,
        private readonly BotRegistry $bots,
    ) {}

    public function index(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $bot = $this->bots->warming($project);

        $hotThreshold = (int) (\App\Services\AI\SettingHelper::behavior($project->id)['hot_lead_threshold'] ?? 70);

        return view('admin.dashboard', [
            'bot' => $bot,
            'stats' => [
                'chats' => Chat::query()->whereHas('bot', fn ($q) => $q
                    ->where('project_id', $project->id)
                    ->where('type', BotRegistry::TYPE_WARMING))->count(),
                'leads' => Lead::query()->where('project_id', $project->id)->count(),
                'hot_leads' => Lead::query()->where('project_id', $project->id)->where('warming_score', '>=', $hotThreshold)->count(),
                'faq' => AiFaqItem::query()->where('project_id', $project->id)->where('is_active', true)->count(),
                'operator_requests' => $this->operators->pendingCount($project->id),
                'referrals' => $this->referrals->statsForProject($project->id),
            ],
        ]);
    }
}
