<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadStatus;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadStatusController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $statuses = LeadStatus::query()->where('project_id', $project->id)->orderBy('sort')->get();

        return view('admin.leads.statuses', compact('statuses'));
    }

    public function update(Request $request, LeadStatus $status): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort' => ['required', 'integer'],
        ]);
        $status->update($data);

        return back()->with('success', 'Статус обновлён.');
    }
}
