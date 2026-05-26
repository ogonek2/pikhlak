<?php

namespace App\Http\Middleware;

use App\Models\Bot;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBotHmac
{
    private const REPLAY_WINDOW = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $botUuid = $request->header('X-Bot-Id');
        $timestamp = $request->header('X-Bot-Timestamp');
        $signature = $request->header('X-Bot-Signature');

        if (! $botUuid || ! $timestamp || ! $signature) {
            return response()->json([
                'errors' => [['code' => 'unauthorized', 'message' => 'Bot auth headers missing.']],
            ], 401);
        }

        if (abs(time() - (int) $timestamp) > self::REPLAY_WINDOW) {
            return response()->json([
                'errors' => [['code' => 'unauthorized', 'message' => 'Request timestamp out of window.']],
            ], 401);
        }

        $bot = Bot::query()->where('uuid', $botUuid)->where('is_active', true)->first();

        if (! $bot) {
            return response()->json([
                'errors' => [['code' => 'unauthorized', 'message' => 'Bot not found.']],
            ], 401);
        }

        $secret = config('pikhlak.bot_hmac_secret');

        if (! $secret) {
            return response()->json([
                'errors' => [['code' => 'server_error', 'message' => 'Bot HMAC secret not configured.']],
            ], 500);
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json([
                'errors' => [['code' => 'unauthorized', 'message' => 'Invalid signature.']],
            ], 401);
        }

        $request->attributes->set('bot', $bot);

        return $next($request);
    }
}
