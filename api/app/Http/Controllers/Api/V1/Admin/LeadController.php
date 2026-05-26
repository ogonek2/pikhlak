<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\LeadStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->success([], $this->paginatedMeta(0, (int) $request->query('page', 1)));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => ['required', 'integer'],
        ]);

        return $this->success([
            'id' => 0,
            'uuid' => '00000000-0000-0000-0000-000000000000',
            'warming_score' => 0,
            ...$this->stub(),
        ], [], 201);
    }

    public function show(int $leadId): JsonResponse
    {
        return $this->success(['id' => $leadId, ...$this->stub()]);
    }

    public function update(int $leadId, Request $request): JsonResponse
    {
        return $this->success(['id' => $leadId, ...$request->all(), ...$this->stub()]);
    }

    public function storeNote(int $leadId, Request $request): JsonResponse
    {
        $request->validate(['body' => ['required', 'string']]);

        return $this->success(['lead_id' => $leadId, 'body' => $request->string('body')], [], 201);
    }
}
