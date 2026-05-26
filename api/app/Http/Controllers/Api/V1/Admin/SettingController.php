<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Bot;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        return $this->success([
            'project' => $project->only(['id', 'uuid', 'name', 'slug', 'settings']),
            ...$this->stub(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        return $this->success($request->all());
    }

    public function bots(Request $request): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $bots = Bot::query()
            ->where('project_id', $project->id)
            ->get(['id', 'uuid', 'name', 'mode', 'is_active', 'created_at']);

        return $this->success($bots);
    }
}
