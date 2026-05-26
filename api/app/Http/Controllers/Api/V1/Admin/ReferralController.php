<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends ApiController
{
    public function campaigns(): JsonResponse
    {
        return $this->success([]);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        return $this->success(['id' => 0, ...$this->stub()], [], 201);
    }

    public function links(): JsonResponse
    {
        return $this->success([]);
    }

    public function storeLink(Request $request): JsonResponse
    {
        return $this->success(['id' => 0, 'code' => 'REF-STUB', ...$this->stub()], [], 201);
    }
}
