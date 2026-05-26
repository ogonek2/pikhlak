<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalculatorController extends ApiController
{
    public function profiles(): JsonResponse
    {
        return $this->success([$this->stub()]);
    }

    public function storeProfile(Request $request): JsonResponse
    {
        return $this->success(['id' => 0, ...$this->stub()], [], 201);
    }

    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'profile_id' => ['required', 'integer'],
            'input' => ['required', 'array'],
        ]);

        return $this->success([
            'output' => ['total' => 0],
            'breakdown' => [],
            ...$this->stub(),
        ]);
    }
}
