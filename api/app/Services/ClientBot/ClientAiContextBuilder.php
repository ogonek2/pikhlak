<?php

namespace App\Services\ClientBot;

use App\Models\RentalClient;
use App\Models\RentalClientInsurance;
use App\Models\RentalClientMaintenance;
use App\Models\RentalClientPayment;
use Illuminate\Support\Collection;

/**
 * Снимок данных одного клиента аренды — единственный источник правды для ИИ клиентского бота.
 */
class ClientAiContextBuilder
{
    public function build(RentalClient $client): string
    {
        $client->loadMissing([
            'phones',
            'vehicles',
            'contracts.vehicle',
            'payments' => fn ($q) => $q->orderBy('due_date'),
            'maintenances.vehicle',
            'insurances',
        ]);

        $contract = $client->activeContract();
        $vehicle = $client->currentVehicle();
        $currency = $contract?->currency ?? 'UAH';
        $symbol = config('client_bot.currency_symbols.'.$currency, 'грн');

        $lines = [
            '=== Данные клиента (только этот клиент) ===',
            'Имя: '.$client->full_name,
            'Статус: '.($client->status ?? 'active'),
        ];

        if ($client->phones->isNotEmpty()) {
            $lines[] = 'Телефоны: '.$client->phones->pluck('phone')->implode(', ');
        }

        if ($vehicle) {
            $lines[] = 'Автомобиль: '.$vehicle->title()
                .($vehicle->plate_number ? ' (госномер: '.$vehicle->plate_number.')' : '');
            if ($vehicle->mileage) {
                $lines[] = 'Пробег: '.$vehicle->mileage;
            }
        } else {
            $lines[] = 'Автомобиль: не указан в договоре';
        }

        if ($contract) {
            $lines[] = 'Договор №: '.$contract->contract_number;
            $lines[] = 'Период аренды: '
                .($contract->rent_start?->format('d.m.Y') ?? '—')
                .' — '
                .($contract->rent_end?->format('d.m.Y') ?? '—');
            if ($contract->total_amount) {
                $lines[] = 'Сумма договора: '
                    .$this->money((float) $contract->total_amount, $symbol);
            }
            if ($contract->first_payment) {
                $lines[] = 'Первый взнос: '
                    .$this->money((float) $contract->first_payment, $symbol);
            }
            if ($contract->monthly_amount) {
                $lines[] = 'Платёж за 4 недели: '
                    .$this->money((float) $contract->monthly_amount, $symbol);
            }
            $lines[] = 'Остаток к оплате: '
                .$this->money($this->remainingAmount($client, $contract), $symbol);
        } else {
            $lines[] = 'Активный договор: не найден';
        }

        $lines[] = '';
        $lines[] = $this->formatPayments($client, $symbol);
        $lines[] = '';
        $lines[] = $this->formatMaintenances($client);
        $lines[] = '';
        $lines[] = $this->formatInsurances($client);

        return implode("\n", $lines);
    }

    private function formatPayments(RentalClient $client, string $symbol): string
    {
        $pending = $client->payments
            ->whereIn('status', ['pending', 'overdue'])
            ->sortBy('due_date')
            ->take(10);

        $paid = $client->payments
            ->where('status', 'paid')
            ->sortByDesc('due_date')
            ->take(3);

        $overdue = $client->payments->where('status', 'overdue')->count();

        $lines = ['Платежи:'];
        if ($overdue > 0) {
            $lines[] = "Просроченных платежей: {$overdue}";
        }

        if ($pending->isEmpty()) {
            $lines[] = 'Ближайших платежей нет (все оплачены или график пуст).';
        } else {
            $lines[] = 'Ближайшие платежи:';
            foreach ($pending as $payment) {
                $lines[] = '- '.$payment->due_date?->format('d.m.Y')
                    .': '.$this->money((float) $payment->amount, $symbol)
                    .' ('.$this->paymentStatusLabel($payment->status).')';
            }
        }

        if ($paid->isNotEmpty()) {
            $lines[] = 'Последние оплаченные:';
            foreach ($paid as $payment) {
                $lines[] = '- '.$payment->due_date?->format('d.m.Y')
                    .': '.$this->money((float) $payment->amount, $symbol);
            }
        }

        return implode("\n", $lines);
    }

    private function formatMaintenances(RentalClient $client): string
    {
        $upcoming = RentalClientMaintenance::query()
            ->where('rental_client_id', $client->id)
            ->whereIn('status', ['planned', 'scheduled'])
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        if ($upcoming->isEmpty()) {
            return 'ТО / сервис: запланированных работ не найдено.';
        }

        $lines = ['ТО / сервис:'];
        foreach ($upcoming as $item) {
            $car = $item->vehicle
                ? trim($item->vehicle->make.' '.$item->vehicle->model)
                : 'автомобиль';
            $lines[] = '- '.$item->title.' ('.$car.'): '.$item->scheduled_at?->format('d.m.Y');
        }

        return implode("\n", $lines);
    }

    private function formatInsurances(RentalClient $client): string
    {
        $active = RentalClientInsurance::query()
            ->where('rental_client_id', $client->id)
            ->where('valid_until', '>=', now()->startOfDay())
            ->orderBy('valid_until')
            ->get();

        if ($active->isEmpty()) {
            return 'Страховка: активных полисов не найдено.';
        }

        $lines = ['Страховка:'];
        foreach ($active as $policy) {
            $lines[] = '- '.$policy->provider.' до '.$policy->valid_until?->format('d.m.Y');
        }

        return implode("\n", $lines);
    }

    private function remainingAmount(RentalClient $client, $contract): float
    {
        if ($contract?->total_amount) {
            $paid = RentalClientPayment::query()
                ->where('rental_client_id', $client->id)
                ->where('status', 'paid')
                ->sum('amount');

            return max(0, (float) $contract->total_amount - (float) $paid);
        }

        return (float) RentalClientPayment::query()
            ->where('rental_client_id', $client->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');
    }

    private function money(float $amount, string $symbol): string
    {
        return number_format($amount, 0, '.', ' ').' '.$symbol;
    }

    private function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            'overdue' => 'просрочен',
            'paid' => 'оплачен',
            default => 'ожидается',
        };
    }
}
