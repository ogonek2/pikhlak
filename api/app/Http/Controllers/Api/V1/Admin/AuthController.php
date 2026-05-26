<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Services\Auth\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use UnexpectedValueException;

class AuthController extends ApiController
{
    public function __construct(private readonly JwtService $jwt) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::query()
            ->where('email', $validated['email'])
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('Invalid credentials.', 'invalid_credentials', 401);
        }

        $user->update(['last_login_at' => now()]);

        return $this->success($this->jwt->issueTokens(
            $user,
            $request->ip(),
            $request->userAgent()
        ));
    }

    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        try {
            $tokens = $this->jwt->refresh(
                $validated['refresh_token'],
                $request->ip(),
                $request->userAgent()
            );
        } catch (UnexpectedValueException) {
            return $this->error('Invalid refresh token.', 'invalid_refresh_token', 401);
        }

        return $this->success($tokens);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->filled('refresh_token')) {
            $this->jwt->revokeRefreshToken($request->string('refresh_token')->toString());
        }

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'roles' => $user->roles()->pluck('name'),
            'projects' => $user->projects()->get(['projects.id', 'projects.uuid', 'projects.name', 'projects.slug']),
        ]);
    }
}
