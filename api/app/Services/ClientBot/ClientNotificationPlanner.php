<?php

namespace App\Services\ClientBot;

use App\Models\ClientNotificationLog;
use App\Models\Project;
use App\Models\RentalClient;
use App\Models\RentalClientInsurance;
use App\Models\RentalClientMaintenance;
use App\Models\RentalClientPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClientNotificationPlanner
{
    public function __construct(private readonly ClientNotificationRuleService $rules) {}

    /** @return Collection<int, array<string, mixed>> */
    public function dueNotifications(Project $project, ?Carbon $onDate = null): Collection
    {
        $today = ($onDate ?? now())->startOfDay();
        $items = collect();

        $clients = RentalClient::query()
            ->where('project_id', $project->id)
            ->where('status', 'active')
            ->where('notifications_enabled', true)
            ->whereNotNull('telegram_chat_id')
            ->get();

        foreach ($clients as $client) {
            $items = $items->merge($this->paymentNotifications($project, $client, $today));
            $items = $items->merge($this->maintenanceNotifications($project, $client, $today));
            $items = $items->merge($this->insuranceNotifications($project, $client, $today));
        }

        return $items;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function paymentNotifications(Project $project, RentalClient $client, Carbon $today): Collection
    {
        $offsets = $this->rules->offsetsFor($project, 'payment');
        $items = collect();

        $payments = RentalClientPayment::query()
            ->where('rental_client_id', $client->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->get();

        foreach ($payments as $payment) {
            if ($payment->due_date->lt($today) && $payment->status === 'pending') {
                $payment->update(['status' => 'overdue']);
            }

            foreach ($offsets as $offset) {
                $notifyDate = $payment->due_date->copy()->addDays($offset);
                if (! $notifyDate->isSameDay($today)) {
                    continue;
                }

                if ($this->alreadySent($client, 'payment', $payment, $offset)) {
                    continue;
                }

                $items->push([
                    'client' => $client,
                    'event_type' => 'payment',
                    'notifiable' => $payment,
                    'offset_days' => $offset,
                    'event_date' => $payment->due_date->toDateString(),
                ]);
            }
        }

        return $items;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function maintenanceNotifications(Project $project, RentalClient $client, Carbon $today): Collection
    {
        $items = collect();

        $maintenances = RentalClientMaintenance::query()
            ->where('rental_client_id', $client->id)
            ->whereIn('status', ['planned', 'scheduled'])
            ->whereNotNull('scheduled_at')
            ->get();

        foreach ($maintenances as $maintenance) {
            $eventType = $this->maintenanceEventType($maintenance->type);
            $offsets = $this->rules->offsetsFor($project, $eventType);

            foreach ($offsets as $offset) {
                $notifyDate = $maintenance->scheduled_at->copy()->addDays($offset);
                if (! $notifyDate->isSameDay($today)) {
                    continue;
                }

                if ($this->alreadySent($client, $eventType, $maintenance, $offset)) {
                    continue;
                }

                $items->push([
                    'client' => $client,
                    'event_type' => $eventType,
                    'notifiable' => $maintenance,
                    'offset_days' => $offset,
                    'event_date' => $maintenance->scheduled_at->toDateString(),
                ]);
            }
        }

        return $items;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function insuranceNotifications(Project $project, RentalClient $client, Carbon $today): Collection
    {
        $offsets = $this->rules->offsetsFor($project, 'insurance');
        $items = collect();

        $policies = RentalClientInsurance::query()
            ->where('rental_client_id', $client->id)
            ->whereNotNull('valid_until')
            ->get();

        foreach ($policies as $policy) {
            foreach ($offsets as $offset) {
                $notifyDate = $policy->valid_until->copy()->addDays($offset);
                if (! $notifyDate->isSameDay($today)) {
                    continue;
                }

                if ($this->alreadySent($client, 'insurance', $policy, $offset)) {
                    continue;
                }

                $items->push([
                    'client' => $client,
                    'event_type' => 'insurance',
                    'notifiable' => $policy,
                    'offset_days' => $offset,
                    'event_date' => $policy->valid_until->toDateString(),
                ]);
            }
        }

        return $items;
    }

    private function alreadySent(RentalClient $client, string $eventType, object $notifiable, int $offset): bool
    {
        return ClientNotificationLog::query()
            ->where('rental_client_id', $client->id)
            ->where('event_type', $eventType)
            ->where('notifiable_type', $notifiable::class)
            ->where('notifiable_id', $notifiable->getKey())
            ->where('offset_days', $offset)
            ->where('status', 'sent')
            ->exists();
    }

    private function maintenanceEventType(string $type): string
    {
        return config('client_bot.maintenance_type_map.'.$type, 'maintenance');
    }
}
