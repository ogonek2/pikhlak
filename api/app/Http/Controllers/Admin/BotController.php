<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BotController extends Controller
{
    public function show(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $bot = Bot::query()->where('project_id', $project->id)->firstOrFail();

        return view('admin.bot.show', compact('bot', 'project'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $bot = Bot::query()->where('project_id', $project->id)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mode' => ['required', 'in:webhook,polling'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $bot->update($data);

        return back()->with('success', 'Настройки бота сохранены.');
    }
}
