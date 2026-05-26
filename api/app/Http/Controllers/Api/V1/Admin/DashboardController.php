<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function overview(Request $request): JsonResponse
    {
        return $this->success([
            'leads_total' => 0,
            'leads_hot' => 0,
            'chats_active' => 0,
            'ai_replies_today' => 0,
            'conversion_rate' => 0.0,
            'period' => $request->query('period', 'week'),
            ...$this->stub(),
        ]);
    }
}
