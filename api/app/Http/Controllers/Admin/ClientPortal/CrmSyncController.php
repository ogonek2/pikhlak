<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ClientBot\Crm\CrmSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CrmSyncController extends Controller
{
    public function store(Request $request, CrmSyncService $sync): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        if (config('client_bot.crm.demo_mode', true) || ! config('client_bot.crm.base_url')) {
            return back()->with('error', 'CRM API не настроен. Укажите CLIENT_BOT_CRM_URL и CLIENT_BOT_CRM_DEMO=false в api/.env');
        }

        try {
            $result = $sync->syncProject($project);
            $msg = "Синхронизировано клиентов: {$result['synced']}";
            if ($result['failed'] > 0) {
                $msg .= ", ошибок: {$result['failed']}";
            }

            return back()->with($result['failed'] > 0 ? 'warning' : 'success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'CRM sync failed: '.$e->getMessage());
        }
    }
}
