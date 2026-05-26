<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends ApiController
{
    public function profiles(): JsonResponse
    {
        return $this->success([$this->stub()]);
    }

    public function storeProfile(Request $request): JsonResponse
    {
        return $this->success(['id' => 0, ...$this->stub()], [], 201);
    }

    public function prompts(int $profileId): JsonResponse
    {
        return $this->success(['profile_id' => $profileId, 'versions' => [], ...$this->stub()]);
    }

    public function storePrompt(int $profileId, Request $request): JsonResponse
    {
        return $this->success(['profile_id' => $profileId, 'version' => 1, ...$this->stub()], [], 201);
    }

    public function publishPrompt(int $profileId, int $version): JsonResponse
    {
        return $this->success(['profile_id' => $profileId, 'version' => $version, 'published' => true]);
    }

    public function faqIndex(): JsonResponse
    {
        return $this->success([]);
    }

    public function faqStore(Request $request): JsonResponse
    {
        return $this->success(['id' => 0, ...$this->stub()], [], 201);
    }

    public function playground(Request $request): JsonResponse
    {
        $request->validate([
            'profile_id' => ['required', 'integer'],
            'message' => ['required', 'string'],
        ]);

        return $this->success([
            'reply' => 'Stub AI reply for: '.$request->string('message'),
            'tokens_in' => 0,
            'tokens_out' => 0,
            'blocked' => false,
        ]);
    }
}
