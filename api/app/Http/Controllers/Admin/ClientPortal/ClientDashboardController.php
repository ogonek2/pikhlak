<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TrafficChannel;
use App\Services\Bot\BotRegistry;
use App\Services\ClientBot\ClientManagerRequestService;
use App\Services\ClientPortal\ClientPortalDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientDashboardController extends Controller
{
    public function __construct(
        private readonly BotRegistry $bots,
        private readonly ClientPortalDashboardService $dashboard,
        private readonly ClientManagerRequestService $managerRequests,
    ) {}

    public function index(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $bot = $this->bots->client($project);

        return view('admin.client.dashboard', [
            'bot' => $bot,
            'stats' => $this->dashboard->stats($project->id),
            'pendingManagerRequests' => $this->managerRequests->pendingCount($project->id),
            'traffic' => $this->dashboard->trafficOverview($project->id),
            'isDemoTraffic' => ! TrafficChannel::query()
                ->where('project_id', $project->id)
                ->where('connection_status', 'connected')
                ->exists(),
        ]);
    }
}
