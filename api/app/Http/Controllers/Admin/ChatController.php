<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Lead;
use App\Models\Project;
use App\Services\AI\SettingHelper;
use App\Services\Chat\ChatStateService;
use App\Services\Chat\OperatorRequestService;
use App\Services\Telegram\TelegramOutboundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatStateService $chatState,
        private readonly OperatorRequestService $operators,
        private readonly TelegramOutboundService $telegram,
    ) {}

    public function index(Request $request): View
    {
        $project = $this->project($request);

        $chatsQuery = Chat::query()
            ->whereHas('bot', fn ($q) => $q->where('project_id', $project->id))
            ->with(['telegramUser', 'lead.status'])
            ->withCount('messages')
            ->withMax('messages', 'created_at');

        if ($filter = $request->string('filter')->toString()) {
            if ($filter === 'operator') {
                $chatsQuery->whereHas('lead', fn ($q) => $q->needsOperator());
            } elseif ($filter === 'human') {
                $chatsQuery->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(state, '$.reply_mode')) = 'human'");
            }
        }

        if ($q = trim($request->string('q')->toString())) {
            $chatsQuery->whereHas('telegramUser', function ($uq) use ($q): void {
                $uq->where('first_name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%");
            });
        }

        $chats = $chatsQuery->orderByDesc('last_activity_at')->paginate(30)->withQueryString();

        $selected = null;
        $messages = collect();
        if ($chatId = $request->integer('chat')) {
            $selected = Chat::query()
                ->whereHas('bot', fn ($bq) => $bq->where('project_id', $project->id))
                ->with(['telegramUser', 'bot', 'lead.status'])
                ->find($chatId);
            if ($selected) {
                $messages = $selected->messages()->orderBy('created_at')->limit(200)->get();
            }
        }

        $replyMode = $selected ? $this->chatState->getMode($selected) : null;

        return view('admin.chats.inbox', [
            'chats' => $chats,
            'selectedChat' => $selected,
            'messages' => $messages,
            'replyMode' => $replyMode,
            'filter' => $filter ?? '',
            'search' => $q ?? '',
            'pendingOperators' => $this->operators->pendingCount($project->id),
            'disableAiOnOperator' => (bool) (SettingHelper::behavior($project->id)['disable_ai_on_operator_request'] ?? true),
        ]);
    }

    public function show(Chat $chat): RedirectResponse
    {
        return redirect()->route('admin.chats.index', ['chat' => $chat->id]);
    }

    public function sendMessage(Request $request, Chat $chat): RedirectResponse
    {
        $this->ensureProjectChat($request, $chat);
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        $chat->loadMissing('bot');
        $bot = $chat->bot;
        if (! $bot) {
            abort(422, 'Бот не найден для этого чата.');
        }
        $this->telegram->sendText($chat, $bot, $data['body'], [
            'sender' => 'admin',
            'user_id' => $request->user()->id,
        ]);

        $chat->update(['last_activity_at' => now()]);
        $chat->lead?->update(['last_contacted_at' => now()]);

        return redirect()
            ->route('admin.chats.index', ['chat' => $chat->id])
            ->with('success', 'Сообщение отправлено в Telegram.');
    }

    public function setMode(Request $request, Chat $chat): RedirectResponse
    {
        $this->ensureProjectChat($request, $chat);
        $data = $request->validate(['mode' => ['required', 'in:ai,human']]);

        $this->chatState->setMode($chat, $data['mode'], $request->user()->id);

        return redirect()
            ->route('admin.chats.index', ['chat' => $chat->id])
            ->with('success', $data['mode'] === 'human' ? 'Режим: вы отвечаете вручную.' : 'Режим: отвечает ИИ.');
    }

    public function acknowledgeOperator(Request $request, Chat $chat): RedirectResponse
    {
        $this->ensureProjectChat($request, $chat);
        $lead = $chat->lead ?? Lead::query()->where('chat_id', $chat->id)->first();
        if ($lead) {
            $this->operators->markHandled($lead, $chat);
        }

        return redirect()
            ->route('admin.chats.index', ['chat' => $chat->id])
            ->with('success', 'Запрос оператора отмечен как обработанный.');
    }

    private function project(Request $request): Project
    {
        return $request->attributes->get('project');
    }

    private function ensureProjectChat(Request $request, Chat $chat): void
    {
        $project = $this->project($request);
        if ($chat->bot?->project_id !== $project->id) {
            abort(404);
        }
    }
}
