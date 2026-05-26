<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Project;
use App\Services\Referral\ReferralTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        private readonly ReferralTrackingService $referrals,
    ) {}

    public function index(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $query = Lead::query()
            ->where('project_id', $project->id)
            ->with(['status', 'chat.telegramUser', 'referralLink']);

        if ($request->string('filter')->toString() === 'operator') {
            $query->needsOperator();
        }

        $leads = $query
            ->orderByRaw('CASE WHEN operator_requested_at IS NOT NULL AND operator_handled_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('operator_requested_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statuses = LeadStatus::query()->where('project_id', $project->id)->orderBy('sort')->get();

        $pendingOperators = Lead::query()
            ->where('project_id', $project->id)
            ->needsOperator()
            ->count();

        return view('admin.leads.index', compact('leads', 'statuses', 'pendingOperators'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status_id' => ['nullable', 'exists:lead_statuses,id'],
            'warming_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
        $lead->update($data);
        $lead->refresh()->load('status');

        if ($lead->status?->is_won) {
            $this->referrals->recordConversion($lead);
        }

        return back()->with('success', 'Лид обновлён.');
    }
}
