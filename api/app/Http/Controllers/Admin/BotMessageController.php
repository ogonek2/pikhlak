<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Bot\BotMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BotMessageController extends Controller
{
    public function __construct(private readonly BotMessageService $messages) {}

    public function edit(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        return view('admin.bot.messages', [
            'messages' => $this->messages->get($project->id),
            'defaults' => $this->messages->defaults(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $data = $request->validate([
            'start_text' => ['required', 'string'],
            'default_reply' => ['required', 'string'],
            'callback_cars' => ['required', 'string'],
            'callback_calculator' => ['required', 'string'],
            'callback_manager' => ['required', 'string'],
            'callback_default' => ['required', 'string'],
            'btn_cars' => ['required', 'string', 'max:64'],
            'btn_calculator' => ['required', 'string', 'max:64'],
            'btn_manager' => ['required', 'string', 'max:64'],
            'typing_duration' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        $this->messages->save($project->id, [
            'start' => [
                'text' => $data['start_text'],
                'parse_mode' => 'HTML',
                'buttons' => [
                    ['label' => $data['btn_cars'], 'callback' => 'cars'],
                    ['label' => $data['btn_calculator'], 'callback' => 'calculator'],
                    ['label' => $data['btn_manager'], 'callback' => 'manager'],
                ],
            ],
            'callbacks' => [
                'cars' => $data['callback_cars'],
                'calculator' => $data['callback_calculator'],
                'manager' => $data['callback_manager'],
                'default' => $data['callback_default'],
            ],
            'default_reply' => $data['default_reply'],
            'typing_duration' => (int) ($data['typing_duration'] ?? 1),
        ]);

        return back()->with('success', 'Тексты бота обновлены. Изменения применятся сразу в Telegram.');
    }
}
