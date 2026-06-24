<?php

namespace App\Services\ClientBot;

use App\Models\RentalClient;
use App\Models\RentalClientInsurance;
use App\Models\RentalClientMaintenance;
use App\Models\RentalClientPayment;

class ClientNotificationSender
{
    public function __construct(
        private readonly ClientNotify $notify,
    ) {}

    /** @param array<string, mixed> $item */
    public function send(array $item): bool
    {
        /** @var RentalClient $client */
        $client = $item['client'];
        $notifiable = $item['notifiable'];
        $eventType = (string) $item['event_type'];
        $offset = (int) $item['offset_days'];

        $type = match (true) {
            $notifiable instanceof RentalClientPayment => 'payment.reminder',
            $notifiable instanceof RentalClientMaintenance => 'maintenance.reminder',
            $notifiable instanceof RentalClientInsurance => 'insurance.reminder',
            default => null,
        };

        if ($type === null) {
            return false;
        }

        return $this->notify->send(
            $client,
            $type,
            [],
            $notifiable,
            [
                'offset_days' => $offset,
                'event_date' => (string) $item['event_date'],
                'log_event_type' => $eventType,
            ],
        );
    }
}
