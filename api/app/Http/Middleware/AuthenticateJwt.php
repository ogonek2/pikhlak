<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UnexpectedValueException;

class AuthenticateJwt
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'errors' => [['code' => 'unauthorized', 'message' => 'Bearer token required.']],
            ], 401);
        }

        try {
            $payload = $this->jwt->decodeAccessToken(substr($header, 7));
            $user = User::query()->where('id', $payload->sub)->where('is_active', true)->first();

            if (! $user) {
                throw new UnexpectedValueException('User not found.');
            }

            $request->setUserResolver(fn () => $user);
        } catch (Throwable) {
            return response()->json([
                'errors' => [['code' => 'unauthorized', 'message' => 'Invalid or expired token.']],
            ], 401);
        }

        return $next($request);
    }
}
