<?php

namespace App\Http\Controllers\Api\V1\Bot;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;

class BotHealthController extends ApiController
{
    public function config(): JsonResponse
    {
        return $this->success([
            'api_version' => '1.0.0',
            'keyboards_version' => 1,
            'rate_limit_per_minute' => 120,
        ]);
    }
}
