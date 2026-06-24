<?php

namespace App\Http\Controllers\Api\V1\Bot;

use App\Http\Controllers\Api\ApiController;
use App\Models\Bot;
use App\Services\Bot\BotDispatchRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotUpdateController extends ApiController
{
    public function __construct(private readonly BotDispatchRouter $router) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'update' => ['required', 'array'],
        ]);

        /** @var Bot $bot */
        $bot = $request->attributes->get('bot');
        $update = $request->input('update');

        return $this->success([
            'update_log_id' => 1,
            'state_version' => 1,
            'actions' => $this->router->dispatch($bot, $update),
            'bot_uuid' => $bot->uuid,
        ]);
    }

    public function ack(int $updateLogId, Request $request): JsonResponse
    {
        return response()->json(null, 204);
    }
}
