<?php

namespace App\Services\ClientBot;

use App\Models\RentalClient;
use App\Models\RentalClientInsurance;
use App\Models\RentalClientMaintenance;
use App\Models\RentalClientPayment;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ClientSelfServiceService
{
    public function commandReply(RentalClient $client, string $command): ?string
    {
        return match ($command) {
            'cmd_balance', 'balance' => $this->balanceReply($client),
            'cmd_payment', 'next_payment' => $this->nextPaymentReply($client),
            'cmd_service', 'next_service' => $this->nextServiceReply($client),
            'cmd_insurance', 'insurance' => $this->insuranceReply($client),
            'cmd_car', 'car' => $this->carReply($client),
            'cmd_payments', 'payments' => $this->paymentsScheduleReply($client),
            default => null,
        };
    }

    public function reply(RentalClient $client, string $text): ?string
    {
        $normalized = Str::lower(trim($text));

        if ($this->matches($normalized, 'balance')) {
            return $this->balanceReply($client);
        }

        if ($this->matches($normalized, 'next_payment')) {
            return $this->nextPaymentReply($client);
        }

        if ($this->matches($normalized, 'next_service')) {
            return $this->nextServiceReply($client);
        }

        if ($this->matches($normalized, 'insurance')) {
            return $this->insuranceReply($client);
        }

        if ($this->matches($normalized, 'car')) {
            return $this->carReply($client);
        }

        return null;
    }

    private function matches(string $text, string $group): bool
    {
        foreach (config('client_bot.self_service_phrases.'.$group, []) as $phrase) {
            if (str_contains($text, Str::lower($phrase))) {
                return true;
            }
        }

        return false;
    }

    private function balanceReply(RentalClient $client): string
    {
        $contract = $client->activeContract();
        $remaining = $this->remainingContractAmount($client, $contract);
        $symbol = config('client_bot.currency_symbols.'.($contract?->currency ?? 'UAH'), 'грн');
        $next = $this->nextPendingPayment($client);

        $lines = [
            'Остаток по договору: <b>'.number_format($remaining, 0, '.', ' ')." {$symbol}</b>.",
        ];

        if ($next) {
            $lines[] = 'Следующий платёж: <b>'.$next->due_date->format('d.m.Y').'</b>';
            $lines[] = 'Сумма: <b>'.number_format((float) $next->amount, 0, '.', ' ')." {$symbol}</b>";
        }

        return implode("\n", $lines);
    }

    private function nextPaymentReply(RentalClient $client): string
    {
        $next = $this->nextPendingPayment($client);
        if (! $next) {
            return 'Ближайших платежей не найдено.';
        }

        $contract = $client->activeContract();
        $symbol = config('client_bot.currency_symbols.'.($contract?->currency ?? 'UAH'), 'грн');

        return "Следующий платёж: <b>{$next->due_date->format('d.m.Y')}</b>\n"
            .'Сумма: <b>'.number_format((float) $next->amount, 0, '.', ' ')." {$symbol}</b>";
    }

    private function nextServiceReply(RentalClient $client): string
    {
        $service = RentalClientMaintenance::query()
            ->where('rental_client_id', $client->id)
            ->whereIn('status', ['planned', 'scheduled'])
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->orderBy('scheduled_at')
            ->first();

        if (! $service) {
            return 'Запланированное ТО не найдено.';
        }

        $vehicle = $service->vehicle ?? $client->currentVehicle();
        $car = $vehicle ? trim($vehicle->make.' '.$vehicle->model) : 'автомобиль';

        return "Следующее обслуживание (<b>{$service->title}</b>) для {$car}: "
            .'<b>'.$service->scheduled_at?->format('d.m.Y').'</b>';
    }

    private function insuranceReply(RentalClient $client): string
    {
        $insurance = RentalClientInsurance::query()
            ->where('rental_client_id', $client->id)
            ->where('valid_until', '>=', now()->startOfDay())
            ->orderBy('valid_until')
            ->first();

        if (! $insurance) {
            return 'Активный страховой полис не найден.';
        }

        return "Страховка <b>{$insurance->provider}</b> действует до "
            .'<b>'.$insurance->valid_until?->format('d.m.Y').'</b>';
    }

    private function carReply(RentalClient $client): string
    {
        $vehicle = $client->currentVehicle();
        if (! $vehicle) {
            return 'В договоре пока не указан автомобиль.';
        }

        $lines = ['Ваш автомобиль: <b>'.$vehicle->title().'</b>'];
        if ($vehicle->plate_number) {
            $lines[] = 'Госномер: <b>'.$vehicle->plate_number.'</b>';
        }

        return implode("\n", $lines);
    }

    private function paymentsScheduleReply(RentalClient $client): string
    {
        $payments = RentalClientPayment::query()
            ->where('rental_client_id', $client->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        if ($payments->isEmpty()) {
            return 'В графике нет предстоящих платежей.';
        }

        $contract = $client->activeContract();
        $symbol = config('client_bot.currency_symbols.'.($contract?->currency ?? 'UAH'), 'грн');
        $lines = ['Ближайшие платежи по графику:'];

        foreach ($payments as $payment) {
            $status = $payment->status === 'overdue' ? ' (просрочен)' : '';
            $lines[] = '• <b>'.$payment->due_date->format('d.m.Y').'</b> — '
                .number_format((float) $payment->amount, 0, '.', ' ')." {$symbol}{$status}";
        }

        return implode("\n", $lines);
    }

    private function nextPendingPayment(RentalClient $client): ?RentalClientPayment
    {
        return RentalClientPayment::query()
            ->where('rental_client_id', $client->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->first();
    }

  private function remainingContractAmount(RentalClient $client, $contract): float
    {
        if ($contract?->total_amount) {
            $paid = RentalClientPayment::query()
                ->where('rental_client_id', $client->id)
                ->where('status', 'paid')
                ->sum('amount');

            return max(0, (float) $contract->total_amount - (float) $paid);
        }

        $pending = RentalClientPayment::query()
            ->where('rental_client_id', $client->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');

        return (float) $pending;
    }
}
