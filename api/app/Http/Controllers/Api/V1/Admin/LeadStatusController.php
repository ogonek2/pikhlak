<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\LeadStatus;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadStatusController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $statuses = LeadStatus::query()
            ->where('project_id', $project->id)
            ->orderBy('sort')
            ->get(['id', 'code', 'name', 'sort', 'color', 'is_won', 'is_lost']);

        return $this->success($statuses);
    }
}
