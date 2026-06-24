<?php

namespace App\Services\ClientBot\Crm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrmApiClient
{
    /** @return array<int, array<string, mixed>> */
    public function fetchClientSnapshots(): array
    {
        if (config('client_bot.crm.demo_mode', true) || ! config('client_bot.crm.base_url')) {
            return [];
        }

        $response = Http::timeout(config('client_bot.crm.timeout', 15))
            ->withToken((string) config('client_bot.crm.api_token'))
            ->acceptJson()
            ->get(rtrim((string) config('client_bot.crm.base_url'), '/').config('client_bot.crm.clients_endpoint', '/clients'));

        if (! $response->successful()) {
            Log::warning('CRM API clients fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('CRM API недоступен: HTTP '.$response->status());
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        return array_is_list($payload) ? $payload : [$payload];
    }

    /** @return array<string, mixed>|null */
    public function fetchClientSnapshot(int $clientId): ?array
    {
        if (config('client_bot.crm.demo_mode', true) || ! config('client_bot.crm.base_url')) {
            return null;
        }

        $response = Http::timeout(config('client_bot.crm.timeout', 15))
            ->withToken((string) config('client_bot.crm.api_token'))
            ->acceptJson()
            ->get(rtrim((string) config('client_bot.crm.base_url'), '/').'/clients/'.$clientId);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return null;
        }

        return $payload['data'] ?? $payload;
    }
}
