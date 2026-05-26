<?php

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use UnexpectedValueException;

class JwtService
{
    public function issueTokens(User $user, ?string $ip = null, ?string $userAgent = null): array
    {
        $accessToken = $this->encodeAccessToken($user);
        $refreshPlain = Str::random(64);

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $refreshPlain),
            'expires_at' => now()->addSeconds(config('jwt.refresh_ttl')),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.access_ttl'),
            'refresh_token' => $refreshPlain,
        ];
    }

    public function encodeAccessToken(User $user): string
    {
        return JWT::encode([
            'sub' => $user->id,
            'uuid' => $user->uuid,
            'type' => 'access',
            'iat' => time(),
            'exp' => time() + config('jwt.access_ttl'),
        ], $this->secret(), config('jwt.algo'));
    }

    public function decodeAccessToken(string $token): object
    {
        $payload = JWT::decode($token, new Key($this->secret(), config('jwt.algo')));

        if (($payload->type ?? null) !== 'access') {
            throw new UnexpectedValueException('Invalid token type.');
        }

        return $payload;
    }

    public function refresh(string $refreshToken, ?string $ip = null, ?string $userAgent = null): array
    {
        $record = RefreshToken::query()
            ->where('token_hash', hash('sha256', $refreshToken))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            throw new UnexpectedValueException('Invalid refresh token.');
        }

        $record->update(['revoked_at' => now()]);

        return $this->issueTokens($record->user, $ip, $userAgent);
    }

    public function revokeRefreshToken(string $refreshToken): void
    {
        RefreshToken::query()
            ->where('token_hash', hash('sha256', $refreshToken))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function secret(): string
    {
        $secret = config('jwt.secret');

        if (! is_string($secret) || $secret === '') {
            throw new UnexpectedValueException('JWT secret is not configured.');
        }

        return str_starts_with($secret, 'base64:')
            ? base64_decode(substr($secret, 7))
            : $secret;
    }
}
