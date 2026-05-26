<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\AI\SettingHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $project = $this->project($request);
        $behavior = SettingHelper::behavior($project->id);

        return view('admin.chats.settings', [
            'disableAiOnOperator' => (bool) ($behavior['disable_ai_on_operator_request'] ?? true),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $project = $this->project($request);

        $behavior = SettingHelper::behavior($project->id);
        $behavior['disable_ai_on_operator_request'] = $request->boolean('disable_ai_on_operator_request');

        SettingHelper::saveBehavior($project->id, $behavior);

        return redirect()
            ->route('admin.chats.settings')
            ->with('success', 'Настройки чатов сохранены.');
    }

    private function project(Request $request): Project
    {
        return $request->attributes->get('project');
    }
}
