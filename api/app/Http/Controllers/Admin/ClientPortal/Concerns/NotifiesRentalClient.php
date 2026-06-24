<?php

namespace App\Http\Controllers\Admin\ClientPortal\Concerns;

use App\Models\RentalClient;
use App\Services\ClientBot\ClientNotify;
use Illuminate\Support\Facades\Log;

trait NotifiesRentalClient
{
    /**
     * @param  array<string, string|int|float|null>  $variables
     * @param  array<string, mixed>  $options
     */
    protected function notifyClient(
        RentalClient $client,
        string $type,
        array $variables = [],
        ?object $notifiable = null,
        array $options = [],
    ): void {
        try {
            ClientNotify::make()->send($client, $type, $variables, $notifiable, $options);
        } catch (\Throwable $e) {
            Log::error('ClientNotify: send failed', [
                'type' => $type,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
