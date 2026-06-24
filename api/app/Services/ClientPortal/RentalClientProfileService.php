<?php

namespace App\Services\ClientPortal;

use App\Models\Project;
use App\Models\RentalClient;
use App\Services\Bot\BotRegistry;
use App\Services\ClientBot\ClientAccountLinker;
use App\Services\Telegram\TelegramBotProfileService;
use Illuminate\Http\Request;

class RentalClientProfileService
{
    public function __construct(
        private readonly BotRegistry $bots,
        private readonly TelegramBotProfileService $telegram,
        private readonly ClientAccountLinker $linker,
    ) {}

    public function loadClient(RentalClient $client): RentalClient
    {
        return $client->load([
            'phones', 'vehicles', 'contracts.vehicle', 'insurances',
            'maintenances.vehicle',
            'payments' => fn ($q) => $q->orderByDesc('due_date'),
        ]);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request, RentalClient $client): array
    {
        $client = $this->loadClient($client);

        /** @var Project $project */
        $project = $request->attributes->get('project');

        $nextPayment = $client->payments
            ->whereIn('status', ['pending', 'overdue'])
            ->sortBy('due_date')
            ->first();
        $nextMaintenance = $client->maintenances
            ->whereIn('status', ['planned', 'scheduled'])
            ->sortBy('scheduled_at')
            ->first();

        $vehicle = $client->currentVehicle();
        $contract = $client->activeContract();
        $botUsername = $this->telegram->username($this->bots->client($project));

        return [
            'client' => [
                'id' => $client->id,
                'uuid' => $client->uuid,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'telegram_user_id' => $client->telegram_user_id,
                'telegram_chat_id' => $client->telegram_chat_id,
                'status' => $client->status,
                'notifications_enabled' => $client->notifications_enabled,
                'notes' => $client->notes,
                'link_token' => $client->link_token,
                'crm_external_id' => $client->crm_external_id,
                'crm_synced_at' => $client->crm_synced_at?->toIso8601String(),
                'phones' => $client->phones->map(fn ($p) => [
                    'id' => $p->id,
                    'label' => $p->label,
                    'phone' => $p->phone,
                    'is_primary' => $p->is_primary,
                ])->values()->all(),
                'vehicles' => $client->vehicles->map(fn ($v) => [
                    'id' => $v->id,
                    'make' => $v->make,
                    'model' => $v->model,
                    'year' => $v->year,
                    'color' => $v->color,
                    'plate_number' => $v->plate_number,
                    'vin' => $v->vin,
                    'mileage' => $v->mileage,
                    'is_current' => $v->is_current,
                    'title' => $v->title(),
                ])->values()->all(),
                'contracts' => $client->contracts->map(fn ($c) => [
                    'id' => $c->id,
                    'rental_client_vehicle_id' => $c->rental_client_vehicle_id,
                    'contract_number' => $c->contract_number,
                    'rent_start' => $c->rent_start?->format('Y-m-d'),
                    'rent_end' => $c->rent_end?->format('Y-m-d'),
                    'monthly_amount' => (float) $c->monthly_amount,
                    'weekly_amount' => $c->weekly_amount !== null ? (float) $c->weekly_amount : null,
                    'period_weeks' => $c->period_weeks,
                    'first_payment' => $c->first_payment !== null ? (float) $c->first_payment : null,
                    'term_years' => $c->term_years,
                    'total_amount' => $c->total_amount !== null ? (float) $c->total_amount : null,
                    'currency' => $c->currency,
                    'buyout_option' => $c->buyout_option,
                    'status' => $c->status,
                    'notes' => $c->notes,
                ])->values()->all(),
                'payments' => $client->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'type' => $p->type,
                    'week_number' => $p->week_number,
                    'period_index' => $p->period_index,
                    'amount' => (float) $p->amount,
                    'due_date' => $p->due_date?->format('Y-m-d'),
                    'paid_at' => $p->paid_at?->format('Y-m-d'),
                    'status' => $p->status,
                    'notes' => $p->notes,
                ])->values()->all(),
                'insurances' => $client->insurances->map(fn ($i) => [
                    'id' => $i->id,
                    'provider' => $i->provider,
                    'policy_number' => $i->policy_number,
                    'valid_from' => $i->valid_from?->format('Y-m-d'),
                    'valid_until' => $i->valid_until?->format('Y-m-d'),
                    'premium_amount' => $i->premium_amount !== null ? (float) $i->premium_amount : null,
                    'coverage_notes' => $i->coverage_notes,
                ])->values()->all(),
                'maintenances' => $client->maintenances->map(fn ($m) => [
                    'id' => $m->id,
                    'rental_client_vehicle_id' => $m->rental_client_vehicle_id,
                    'type' => $m->type,
                    'title' => $m->title,
                    'scheduled_at' => $m->scheduled_at?->format('Y-m-d'),
                    'completed_at' => $m->completed_at?->format('Y-m-d'),
                    'mileage_at' => $m->mileage_at,
                    'cost' => $m->cost !== null ? (float) $m->cost : null,
                    'status' => $m->status,
                    'notes' => $m->notes,
                ])->values()->all(),
            ],
            'summary' => [
                'vehicle' => $vehicle ? [
                    'id' => $vehicle->id,
                    'title' => $vehicle->title(),
                    'plate_number' => $vehicle->plate_number,
                ] : null,
                'contract' => $contract ? [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'monthly_amount' => (float) $contract->monthly_amount,
                    'currency' => $contract->currency,
                ] : null,
                'nextPayment' => $nextPayment ? [
                    'id' => $nextPayment->id,
                    'amount' => (float) $nextPayment->amount,
                    'due_date' => $nextPayment->due_date?->format('Y-m-d'),
                    'status' => $nextPayment->status,
                ] : null,
                'nextMaintenance' => $nextMaintenance ? [
                    'id' => $nextMaintenance->id,
                    'title' => $nextMaintenance->title,
                    'scheduled_at' => $nextMaintenance->scheduled_at?->format('Y-m-d'),
                ] : null,
                'pendingPayments' => $client->payments->whereIn('status', ['pending', 'overdue'])->count(),
                'overduePayments' => $client->payments->where('status', 'overdue')->count(),
                'telegram' => $this->telegramSummary($client),
            ],
            'config' => [
                'statuses' => config('client_portal.client_statuses', []),
                'paymentStatuses' => config('client_portal.payment_statuses', []),
                'paymentTypes' => config('client_portal.payment_types', []),
                'contractStatuses' => config('client_portal.contract_statuses', []),
                'maintenanceStatuses' => config('client_portal.maintenance_statuses', []),
                'maintenanceTypes' => config('client_portal.maintenance_types', []),
                'currencySymbols' => config('client_bot.currency_symbols', []),
                'botUsername' => $botUsername,
            ],
            'urls' => $this->urls($client),
        ];
    }

    /** @return array<string, mixed> */
    private function urls(RentalClient $client): array
    {
        return [
            'index' => route('admin.client.clients.index'),
            'update' => route('admin.client.clients.update', $client),
            'refresh' => route('admin.client.clients.profile-data', $client),
            'claimTelegram' => route('admin.client.clients.claim-telegram', $client),
            'phones' => [
                'store' => route('admin.client.clients.phones.store', $client),
                'destroy' => route('admin.client.clients.phones.destroy', [$client, '__ID__']),
            ],
            'vehicles' => [
                'store' => route('admin.client.clients.vehicles.store', $client),
                'update' => route('admin.client.clients.vehicles.update', [$client, '__ID__']),
                'destroy' => route('admin.client.clients.vehicles.destroy', [$client, '__ID__']),
            ],
            'contracts' => [
                'store' => route('admin.client.clients.contracts.store', $client),
                'update' => route('admin.client.clients.contracts.update', [$client, '__ID__']),
                'destroy' => route('admin.client.clients.contracts.destroy', [$client, '__ID__']),
            ],
            'payments' => [
                'store' => route('admin.client.clients.payments.store', $client),
                'update' => route('admin.client.clients.payments.update', [$client, '__ID__']),
                'destroy' => route('admin.client.clients.payments.destroy', [$client, '__ID__']),
                'paid' => route('admin.client.clients.payments.paid', [$client, '__ID__']),
            ],
            'insurances' => [
                'store' => route('admin.client.clients.insurances.store', $client),
                'update' => route('admin.client.clients.insurances.update', [$client, '__ID__']),
                'destroy' => route('admin.client.clients.insurances.destroy', [$client, '__ID__']),
            ],
            'maintenances' => [
                'store' => route('admin.client.clients.maintenances.store', $client),
                'update' => route('admin.client.clients.maintenances.update', [$client, '__ID__']),
                'destroy' => route('admin.client.clients.maintenances.destroy', [$client, '__ID__']),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function telegramSummary(RentalClient $client): array
    {
        if ($client->isTelegramLinked()) {
            return [
                'status' => 'linked',
                'chat_id' => $client->resolveTelegramChatId(),
            ];
        }

        $twin = $this->linker->findPhoneTwinWithTelegram($client);
        if ($twin) {
            return [
                'status' => 'sibling',
                'sibling_id' => $twin->id,
                'sibling_name' => $twin->full_name,
                'chat_id' => $twin->resolveTelegramChatId(),
            ];
        }

        return ['status' => 'none'];
    }
}
