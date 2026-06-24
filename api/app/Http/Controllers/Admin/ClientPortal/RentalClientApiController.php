<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\RentalClient;
use App\Services\ClientPortal\RentalClientProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalClientApiController extends Controller
{
    public function __construct(
        private readonly RentalClientProfileService $profile,
    ) {}

    public function show(Request $request, RentalClient $client): JsonResponse
    {
        $this->ensureProjectClient($request, $client);

        return response()->json([
            'data' => $this->profile->toArray($request, $client),
        ]);
    }

    private function ensureProjectClient(Request $request, RentalClient $client): void
    {
        if ($client->project_id !== $request->attributes->get('project')->id) {
            abort(404);
        }
    }
}
