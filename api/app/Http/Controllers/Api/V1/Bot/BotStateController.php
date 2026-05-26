<?php

namespace App\Http\Controllers\Api\V1\Bot;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;

class BotStateController extends ApiController
{
    public function show(int $telegramChatId): JsonResponse
    {
        return $this->success([
            'state_version' => 0,
            'state' => [],
        ]);
    }
}
