<?php

namespace App\Http\Controllers\Admin\ClientPortal\Concerns;

use App\Models\RentalClient;
use App\Services\ClientPortal\RentalClientProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RespondsWithClientProfile
{
    protected function clientProfileResponse(Request $request, RentalClient $client, string $message): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'data' => app(RentalClientProfileService::class)->toArray($request, $client),
            ]);
        }

        return back()->with('success', $message);
    }
}
