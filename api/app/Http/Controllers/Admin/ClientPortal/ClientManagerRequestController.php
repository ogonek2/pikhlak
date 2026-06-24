<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\ClientManagerRequest;
use App\Models\Project;
use App\Services\Bot\BotRegistry;
use App\Services\ClientBot\ClientManagerRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientManagerRequestController extends Controller
{
    public function __construct(
        private readonly BotRegistry $bots,
        private readonly ClientManagerRequestService $requests,
    ) {}

    public function index(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $status = $request->string('status')->toString() ?: null;
        if ($status === 'all') {
            $status = null;
        }

        return view('admin.client.bot.manager-requests', [
            'bot' => $this->bots->client($project),
            'requests' => $this->requests->paginateForProject($project->id, $status),
            'pendingCount' => $this->requests->pendingCount($project->id),
            'statusFilter' => $request->string('status')->toString() ?: 'pending',
            'managerSettings' => $this->requests->settingsForBot($this->bots->client($project)),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $bot = $this->bots->client($project);

        $data = $request->validate([
            'manager_phone' => ['nullable', 'string', 'max:64'],
            'manager_confirm_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->requests->saveSettings(
            $bot,
            $data['manager_phone'] ?? null,
            $data['manager_confirm_message'] ?? null,
        );

        return back()->with('success', 'Настройки менеджера сохранены.');
    }

    public function inProgress(Request $request, ClientManagerRequest $managerRequest): RedirectResponse
    {
        $this->ensureProjectRequest($request, $managerRequest);
        $this->requests->markInProgress($managerRequest, (int) $request->user()->id);

        return back()->with('success', 'Заявка взята в работу.');
    }

    public function resolve(Request $request, ClientManagerRequest $managerRequest): RedirectResponse
    {
        $this->ensureProjectRequest($request, $managerRequest);
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:2000']]);
        $this->requests->markResolved($managerRequest, (int) $request->user()->id, $data['admin_notes'] ?? null);

        return back()->with('success', 'Заявка отмечена как обработанная.');
    }

    public function cancel(Request $request, ClientManagerRequest $managerRequest): RedirectResponse
    {
        $this->ensureProjectRequest($request, $managerRequest);
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:2000']]);
        $this->requests->cancel($managerRequest, (int) $request->user()->id, $data['admin_notes'] ?? null);

        return back()->with('success', 'Заявка отменена.');
    }

    private function ensureProjectRequest(Request $request, ClientManagerRequest $managerRequest): void
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        if ($managerRequest->project_id !== $project->id) {
            abort(404);
        }
    }
}
