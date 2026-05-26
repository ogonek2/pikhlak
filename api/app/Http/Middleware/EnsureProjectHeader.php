<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectId = $request->header('X-Project-Id');

        if (! $projectId || ! is_numeric($projectId)) {
            return response()->json([
                'errors' => [['code' => 'project_required', 'message' => 'X-Project-Id header is required.']],
            ], 400);
        }

        $project = Project::query()->where('id', (int) $projectId)->where('is_active', true)->first();

        if (! $project) {
            return response()->json([
                'errors' => [['code' => 'project_not_found', 'message' => 'Project not found or inactive.']],
            ], 404);
        }

        $request->attributes->set('project', $project);

        return $next($request);
    }
}
