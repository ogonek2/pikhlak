<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->success([], $this->paginatedMeta(0, (int) $request->query('page', 1)));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'make' => ['required', 'string'],
            'model' => ['required', 'string'],
        ]);

        return $this->success(['id' => 0, ...$request->only(['make', 'model']), ...$this->stub()], [], 201);
    }

    public function show(int $carId): JsonResponse
    {
        return $this->success(['id' => $carId, ...$this->stub()]);
    }

    public function update(int $carId, Request $request): JsonResponse
    {
        return $this->success(['id' => $carId, ...$request->all(), ...$this->stub()]);
    }

    public function destroy(int $carId): JsonResponse
    {
        return response()->json(null, 204);
    }

    public function import(Request $request): JsonResponse
    {
        return $this->success(['job_id' => 0, ...$this->stub('Import job queued')], [], 202);
    }
}
