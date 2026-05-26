<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends ApiController
{
    public function dialogs(): JsonResponse
    {
        return $this->success(['items' => [], ...$this->stub()]);
    }

    public function aiEffectiveness(): JsonResponse
    {
        return $this->success(['items' => [], ...$this->stub()]);
    }

    public function hotLeads(): JsonResponse
    {
        return $this->success(['items' => [], ...$this->stub()]);
    }
}
