<?php

namespace App\Services\ClientBot\Crm;

use App\Models\CrmSyncLog;
use App\Models\Project;
use App\Models\RentalClient;
use App\Models\RentalClientContract;
use App\Models\RentalClientInsurance;
use App\Models\RentalClientMaintenance;
use App\Models\RentalClientPayment;
use App\Models\RentalClientPhone;
use App\Models\RentalClientVehicle;
use App\Services\Bot\BotRegistry;
use Illuminate\Support\Str;

class CrmSyncService
{
    public function __construct(
        private readonly CrmApiClient $api,
        private readonly CrmSnapshotMapper $mapper,
        private readonly BotRegistry $bots,
    ) {}

    /** @return array{synced: int, failed: int, errors: array<int, string>, log: CrmSyncLog} */
    public function syncProject(Project $project): array
    {
        $log = CrmSyncLog::query()->create([
            'project_id' => $project->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $snapshots = $this->api->fetchClientSnapshots();
        $result = ['synced' => 0, 'failed' => 0, 'errors' => [], 'log' => $log];

        if ($snapshots === []) {
            $log->update([
                'status' => 'success',
                'message' => 'Нет данных от CRM',
                'finished_at' => now(),
            ]);

            return $result;
        }

        $bot = $this->bots->client($project);

        foreach ($snapshots as $snapshot) {
            try {
                $this->upsertSnapshot($project, $bot->id, $snapshot);
                $result['synced']++;
            } catch (\Throwable $e) {
                $result['failed']++;
                $result['errors'][] = ($snapshot['client_id'] ?? $snapshot['id'] ?? '?').': '.$e->getMessage();
            }
        }

        $log->update([
            'status' => $result['failed'] === 0 ? 'success' : ($result['synced'] > 0 ? 'partial' : 'failed'),
            'clients_synced' => $result['synced'],
            'clients_failed' => $result['failed'],
            'message' => $result['errors'] === [] ? null : implode('; ', array_slice($result['errors'], 0, 5)),
            'details' => ['errors' => $result['errors']],
            'finished_at' => now(),
        ]);

        return $result;
    }

    /** @param array<string, mixed> $snapshot */
    public function upsertSnapshot(Project $project, int $botId, array $snapshot): RentalClient
    {
        $externalId = $this->mapper->clientId($snapshot);
        if ($externalId <= 0) {
            throw new \InvalidArgumentException('client_id обязателен');
        }

        $client = RentalClient::query()->firstOrNew([
            'project_id' => $project->id,
            'crm_external_id' => $externalId,
        ]);

        $attrs = $this->mapper->clientAttributes($snapshot);
        $client->fill([
            'bot_id' => $botId,
            'full_name' => $attrs['full_name'] ?? $client->full_name ?? 'Клиент',
            'email' => $attrs['email'] ?? $client->email,
            'status' => $client->exists
                ? ($client->status === 'archived' ? 'archived' : ($attrs['status'] ?? $client->status))
                : ($attrs['status'] ?? 'active'),
            'notifications_enabled' => $client->notifications_enabled ?? true,
            'crm_synced_at' => now(),
            'metadata' => array_merge($client->metadata ?? [], [
                'crm_last_snapshot' => $snapshot,
                'crm_synced_at' => now()->toIso8601String(),
            ]),
        ]);

        if (! $client->link_token) {
            $client->link_token = Str::lower(Str::random(12));
        }

        // Локальные поля Telegram не перезаписываем при синке
        $client->save();

        if ($phone = $this->mapper->phone($snapshot)) {
            RentalClientPhone::query()->updateOrCreate(
                ['rental_client_id' => $client->id, 'is_primary' => true],
                ['label' => 'mobile', 'phone' => $phone]
            );
        }

        $vehicleId = null;
        if ($vehicle = $this->mapper->vehicle($snapshot)) {
            $vehicleModel = RentalClientVehicle::query()->updateOrCreate(
                ['rental_client_id' => $client->id, 'is_current' => true],
                $vehicle
            );
            $vehicleId = $vehicleModel->id;
        }

        if ($contract = $this->mapper->contract($snapshot)) {
            $match = ['rental_client_id' => $client->id];
            if (! empty($contract['crm_external_id'])) {
                $match['crm_external_id'] = $contract['crm_external_id'];
            } elseif (! empty($contract['contract_number'])) {
                $match['contract_number'] = $contract['contract_number'];
            }

            $contractRow = RentalClientContract::query()->updateOrCreate($match, [
                ...$contract,
                'rental_client_vehicle_id' => $vehicleId,
            ]);
        }

        foreach ($this->mapper->payments($snapshot) as $payment) {
            if (empty($payment['due_date']) || empty($payment['amount'])) {
                continue;
            }

            $match = ['rental_client_id' => $client->id];
            if (! empty($payment['crm_external_id'])) {
                $match['crm_external_id'] = $payment['crm_external_id'];
            } else {
                $match['type'] = $payment['type'];
                $match['due_date'] = $payment['due_date'];
            }

            RentalClientPayment::query()->updateOrCreate($match, $payment);
        }

        foreach ($this->mapper->maintenances($snapshot) as $maintenance) {
            if (empty($maintenance['scheduled_at'])) {
                continue;
            }

            $match = ['rental_client_id' => $client->id];
            if (! empty($maintenance['crm_external_id'])) {
                $match['crm_external_id'] = $maintenance['crm_external_id'];
            } else {
                $match['scheduled_at'] = $maintenance['scheduled_at'];
                $match['type'] = $maintenance['type'];
            }

            RentalClientMaintenance::query()->updateOrCreate($match, [
                ...$maintenance,
                'rental_client_vehicle_id' => $vehicleId,
            ]);
        }

        foreach ($this->mapper->insurances($snapshot) as $insurance) {
            if (empty($insurance['valid_until']) && empty($insurance['provider'])) {
                continue;
            }

            $match = ['rental_client_id' => $client->id];
            if (! empty($insurance['crm_external_id'])) {
                $match['crm_external_id'] = $insurance['crm_external_id'];
            } else {
                $match['provider'] = $insurance['provider'];
            }

            RentalClientInsurance::query()->updateOrCreate($match, $insurance);
        }

        return $client->fresh(['phones', 'vehicles', 'contracts', 'payments', 'maintenances', 'insurances']);
    }
}
