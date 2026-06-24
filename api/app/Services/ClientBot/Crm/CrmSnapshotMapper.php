<?php

namespace App\Services\ClientBot\Crm;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Нормализует ответ внешней CRM в структуры для локальной БД.
 * Поддерживает плоский объект (как в ТЗ) и вложенные массивы payments / maintenances / insurances.
 */
class CrmSnapshotMapper
{
    /** @param array<string, mixed> $snapshot */
    public function clientId(array $snapshot): int
    {
        return (int) ($snapshot['client_id'] ?? $snapshot['id'] ?? 0);
    }

    /** @param array<string, mixed> $snapshot */
    public function clientAttributes(array $snapshot): array
    {
        return array_filter([
            'full_name' => (string) ($snapshot['name'] ?? $snapshot['full_name'] ?? ''),
            'email' => $snapshot['email'] ?? null,
            'status' => $this->mapClientStatus((string) ($snapshot['status'] ?? 'active')),
        ], fn ($v) => $v !== '' && $v !== null);
    }

    /** @param array<string, mixed> $snapshot */
    public function phone(array $snapshot): ?string
    {
        $phone = trim((string) ($snapshot['phone'] ?? $snapshot['mobile'] ?? ''));

        return $phone !== '' ? $phone : null;
    }

    /** @param array<string, mixed> $snapshot */
    public function vehicle(array $snapshot): ?array
    {
        $car = trim((string) ($snapshot['car'] ?? $snapshot['vehicle'] ?? ''));
        if ($car === '' && empty($snapshot['make']) && empty($snapshot['model'])) {
            return null;
        }

        [$make, $model] = $car !== ''
            ? $this->splitCarName($car)
            : [(string) ($snapshot['make'] ?? ''), (string) ($snapshot['model'] ?? '')];

        return array_filter([
            'make' => $make,
            'model' => $model,
            'year' => isset($snapshot['year']) ? (int) $snapshot['year'] : null,
            'plate_number' => $snapshot['plate_number'] ?? $snapshot['plate'] ?? null,
            'vin' => $snapshot['vin'] ?? null,
            'mileage' => isset($snapshot['mileage']) ? (int) $snapshot['mileage'] : null,
            'is_current' => true,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** @param array<string, mixed> $snapshot */
    public function contract(array $snapshot): ?array
    {
        $number = trim((string) ($snapshot['contract_number'] ?? $snapshot['contract_no'] ?? ''));
        if ($number === '' && empty($snapshot['monthly_amount']) && empty($snapshot['contract_start'])) {
            return null;
        }

        return array_filter([
            'crm_external_id' => $this->externalKey($snapshot, 'contract_id', 'contract_number', $number),
            'contract_number' => $number !== '' ? $number : null,
            'rent_start' => $this->date($snapshot['contract_start'] ?? $snapshot['rent_start'] ?? null),
            'rent_end' => $this->date($snapshot['contract_end'] ?? $snapshot['rent_end'] ?? null),
            'monthly_amount' => isset($snapshot['monthly_amount']) ? (float) $snapshot['monthly_amount'] : null,
            'total_amount' => isset($snapshot['total_amount']) ? (float) $snapshot['total_amount'] : null,
            'currency' => strtoupper((string) ($snapshot['currency'] ?? 'UAH')),
            'buyout_option' => (bool) ($snapshot['buyout_option'] ?? true),
            'status' => (string) ($snapshot['contract_status'] ?? 'active'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** @return array<int, array<string, mixed>> */
    public function payments(array $snapshot): array
    {
        if (is_array($snapshot['payments'] ?? null) && $snapshot['payments'] !== []) {
            return array_map(fn (array $row) => $this->normalizePayment($row), $snapshot['payments']);
        }

        if (empty($snapshot['payment_date']) && empty($snapshot['payment_amount'])) {
            return [];
        }

        return [[
            'crm_external_id' => $this->externalKey($snapshot, 'payment_id'),
            'type' => (string) ($snapshot['payment_type'] ?? 'rent'),
            'amount' => (float) ($snapshot['payment_amount'] ?? 0),
            'due_date' => $this->date($snapshot['payment_date'] ?? null),
            'status' => $this->mapPaymentStatus((string) ($snapshot['payment_status'] ?? 'pending')),
            'paid_at' => $this->date($snapshot['payment_paid_at'] ?? null),
        ]];
    }

    /** @return array<int, array<string, mixed>> */
    public function maintenances(array $snapshot): array
    {
        if (is_array($snapshot['maintenances'] ?? null) && $snapshot['maintenances'] !== []) {
            return array_map(fn (array $row) => $this->normalizeMaintenance($row), $snapshot['maintenances']);
        }

        if (empty($snapshot['service_date'])) {
            return [];
        }

        return [[
            'crm_external_id' => $this->externalKey($snapshot, 'service_id'),
            'type' => $this->mapServiceType((string) ($snapshot['service_type'] ?? 'ТО')),
            'title' => (string) ($snapshot['service_type'] ?? 'ТО'),
            'scheduled_at' => $this->date($snapshot['service_date'] ?? null),
            'status' => (string) ($snapshot['service_status'] ?? 'planned'),
        ]];
    }

    /** @return array<int, array<string, mixed>> */
    public function insurances(array $snapshot): array
    {
        if (is_array($snapshot['insurances'] ?? null) && $snapshot['insurances'] !== []) {
            return array_map(fn (array $row) => $this->normalizeInsurance($row), $snapshot['insurances']);
        }

        if (empty($snapshot['insurance_provider']) && empty($snapshot['insurance_valid_until'])) {
            return [];
        }

        return [[
            'crm_external_id' => $this->externalKey($snapshot, 'insurance_id'),
            'provider' => (string) ($snapshot['insurance_provider'] ?? 'Страховка'),
            'policy_number' => $snapshot['insurance_policy'] ?? $snapshot['policy_number'] ?? null,
            'valid_from' => $this->date($snapshot['insurance_valid_from'] ?? null),
            'valid_until' => $this->date($snapshot['insurance_valid_until'] ?? null),
            'premium_amount' => isset($snapshot['insurance_premium']) ? (float) $snapshot['insurance_premium'] : null,
        ]];
    }

    /** @param array<string, mixed> $row */
    private function normalizePayment(array $row): array
    {
        return array_filter([
            'crm_external_id' => $this->externalKey($row, 'payment_id', 'id'),
            'type' => (string) ($row['type'] ?? $row['payment_type'] ?? 'rent'),
            'amount' => (float) ($row['amount'] ?? $row['payment_amount'] ?? 0),
            'due_date' => $this->date($row['due_date'] ?? $row['payment_date'] ?? null),
            'status' => $this->mapPaymentStatus((string) ($row['status'] ?? $row['payment_status'] ?? 'pending')),
            'paid_at' => $this->date($row['paid_at'] ?? $row['payment_paid_at'] ?? null),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** @param array<string, mixed> $row */
    private function normalizeMaintenance(array $row): array
    {
        return array_filter([
            'crm_external_id' => $this->externalKey($row, 'service_id', 'id'),
            'type' => $this->mapServiceType((string) ($row['type'] ?? $row['service_type'] ?? 'ТО')),
            'title' => (string) ($row['title'] ?? $row['service_type'] ?? 'ТО'),
            'scheduled_at' => $this->date($row['scheduled_at'] ?? $row['service_date'] ?? null),
            'status' => (string) ($row['status'] ?? $row['service_status'] ?? 'planned'),
            'cost' => isset($row['cost']) ? (float) $row['cost'] : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** @param array<string, mixed> $row */
    private function normalizeInsurance(array $row): array
    {
        return array_filter([
            'crm_external_id' => $this->externalKey($row, 'insurance_id', 'id'),
            'provider' => (string) ($row['provider'] ?? $row['insurance_provider'] ?? 'Страховка'),
            'policy_number' => $row['policy_number'] ?? $row['insurance_policy'] ?? null,
            'valid_from' => $this->date($row['valid_from'] ?? $row['insurance_valid_from'] ?? null),
            'valid_until' => $this->date($row['valid_until'] ?? $row['insurance_valid_until'] ?? null),
            'premium_amount' => isset($row['premium_amount']) ? (float) $row['premium_amount'] : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** @param array<string, mixed> $data */
    private function externalKey(array $data, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (! empty($data[$key])) {
                return (string) $data[$key];
            }
        }

        return null;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->toDateString();
    }

    /** @return array{0: string, 1: string} */
    private function splitCarName(string $car): array
    {
        $parts = preg_split('/\s+/', $car, 2) ?: [];

        return [$parts[0] ?? $car, $parts[1] ?? ''];
    }

    private function mapClientStatus(string $status): string
    {
        return match (Str::lower($status)) {
            'paused', 'pause' => 'paused',
            'completed', 'done', 'closed' => 'completed',
            'archived' => 'archived',
            default => 'active',
        };
    }

    private function mapPaymentStatus(string $status): string
    {
        return match (Str::lower($status)) {
            'paid', 'оплачен', 'оплачено' => 'paid',
            'overdue', 'просрочен' => 'overdue',
            'cancelled', 'canceled', 'отменён' => 'cancelled',
            default => 'pending',
        };
    }

    private function mapServiceType(string $label): string
    {
        $normalized = Str::lower($label);

        return match (true) {
            str_contains($normalized, 'масл') => 'oil_change',
            str_contains($normalized, 'техосмотр') => 'inspection',
            str_contains($normalized, 'то'), str_contains($normalized, 'сервис') => 'service',
            default => 'other',
        };
    }
}
