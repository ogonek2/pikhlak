<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->success([], $this->paginatedMeta(0, (int) $request->query('page', 1)));
    }

    public function show(int $chatId): JsonResponse
    {
        return $this->success([
            'id' => $chatId,
            'telegram_chat_id' => 0,
            'type' => 'private',
            'state_version' => 0,
            'last_activity_at' => null,
            ...$this->stub(),
        ]);
    }

    public function messages(int $chatId, Request $request): JsonResponse
    {
        return $this->success([], $this->paginatedMeta(0, (int) $request->query('page', 1)));
    }

    public function reply(int $chatId, Request $request): JsonResponse
    {
        $request->validate(['text' => ['required', 'string']]);

        return $this->success([
            'update_log_id' => 0,
            'state_version' => 0,
            'actions' => [
                ['type' => 'send_message', 'chat_id' => 0, 'text' => $request->string('text')],
            ],
            ...$this->stub('Reply queued'),
        ], [], 202);
    }
}
