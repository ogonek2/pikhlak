<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Bot\BotRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BotController extends Controller
{
    public function __construct(private readonly BotRegistry $bots) {}

    public function show(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $bot = $this->bots->warming($project);

        return view('admin.bot.show', compact('bot', 'project'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $bot = $this->bots->warming($project);

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
