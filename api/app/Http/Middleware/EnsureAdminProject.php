<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminProject
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('admin_project_id')) {
            $project = Project::query()->where('is_active', true)->orderBy('id')->first();
            if ($project) {
                session(['admin_project_id' => $project->id]);
            }
        }

        $projectId = session('admin_project_id');
        if ($projectId) {
            $project = Project::query()->find($projectId);
            if ($project) {
                $request->attributes->set('project', $project);
                view()->share('currentProject', $project);
            }
        }

        return $next($request);
    }
}
