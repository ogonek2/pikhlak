<?php

namespace App\Services\Rental;

use App\Models\Car;
use App\Models\RentalClient;
use App\Models\RentalClientContract;
use App\Models\RentalClientMaintenance;
use App\Models\RentalClientPayment;
use App\Models\RentalClientVehicle;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RentalClientOnboardingService
{
    public function __construct(
        private readonly RentBuyoutCalculator $calculator,
    ) {}

    /**
     * @param  array{
     *     car_id: int,
     *     first_payment: float|int|string,
     *     term_years: int,
     *     overpayment_rate?: float|null,
     *     rent_start?: string|null,
     *     plate_number?: string|null,
     *     contract_number?: string|null,
     * }  $input
     * @return array{calculation: array<string, mixed>, contract: RentalClientContract, vehicle: RentalClientVehicle}
     */
    public function provision(RentalClient $client, array $input): array
    {
        $car = Car::query()
            ->where('project_id', $client->project_id)
            ->findOrFail($input['car_id']);

        $calculation = $this->calculator->calculate([
            'car_price' => (float) $car->price,
            'first_payment' => $input['first_payment'],
            'term_years' => $input['term_years'],
            'overpayment_rate' => $input['overpayment_rate'] ?? null,
            'currency' => $car->currency ?? config('rent_buyout.currency', 'USD'),
        ]);

        $start = isset($input['rent_start'])
            ? Carbon::parse($input['rent_start'])->startOfDay()
            : now()->startOfDay();

        $vehicle = $client->vehicles()->create([
            'car_id' => $car->id,
            'make' => $car->make,
            'model' => $car->model,
            'year' => $car->year,
            'plate_number' => $input['plate_number'] ?? null,
            'vin' => $car->vin,
            'mileage' => $car->mileageKm(),
            'is_current' => true,
        ]);

        $rentEnd = $start->copy()->addWeeks((int) $calculation['total_weeks']);

        $contract = $client->contracts()->create([
            'rental_client_vehicle_id' => $vehicle->id,
            'car_id' => $car->id,
            'contract_number' => $input['contract_number'] ?? $this->generateContractNumber($client),
            'rent_start' => $start->toDateString(),
            'rent_end' => $rentEnd->toDateString(),
            'monthly_amount' => $calculation['period_payment'],
            'weekly_amount' => $calculation['weekly_payment'],
            'period_weeks' => $calculation['weeks_per_period'],
            'total_amount' => $calculation['total_cost'],
            'first_payment' => $calculation['first_payment'],
            'term_years' => $calculation['term_years'],
            'overpayment_rate' => $calculation['overpayment_rate'],
            'currency' => $calculation['currency'],
            'buyout_option' => true,
            'status' => 'active',
            'calculation_snapshot' => $calculation,
            'notes' => 'Создано автоматически из калькулятора аренды с выкупом.',
        ]);

        $this->generatePeriodPayments($client, $contract, $calculation, $start);
        $this->generateMaintenanceSchedule($client, $vehicle, $start, (int) $calculation['total_weeks']);

        return [
            'calculation' => $calculation,
            'contract' => $contract,
            'vehicle' => $vehicle,
        ];
    }

    /** @param  array<string, mixed>  $calculation */
    private function generatePeriodPayments(
        RentalClient $client,
        RentalClientContract $contract,
        array $calculation,
        Carbon $start,
    ): void {
        $totalWeeks = (int) $calculation['total_weeks'];
        $weeksPerPeriod = (int) $calculation['weeks_per_period'];
        $totalPeriods = (int) $calculation['total_periods'];
        $periodAmount = (float) $calculation['period_payment'];
        $weeklyAmount = (float) $calculation['weekly_payment'];

        if ($calculation['first_payment'] > 0) {
            $client->payments()->create([
                'type' => 'rent',
                'amount' => $calculation['first_payment'],
                'due_date' => $start->toDateString(),
                'status' => 'pending',
                'week_number' => 0,
                'period_index' => 0,
                'notes' => 'Первый взнос',
            ]);
        }

        for ($period = 1; $period <= $totalPeriods; $period++) {
            $startWeek = ($period - 1) * $weeksPerPeriod + 1;
            $endWeek = min($period * $weeksPerPeriod, $totalWeeks);
            $weeksInPeriod = $endWeek - $startWeek + 1;
            $amount = $weeksInPeriod === $weeksPerPeriod
                ? $periodAmount
                : round($weeklyAmount * $weeksInPeriod, 2);

            $client->payments()->create([
                'type' => 'rent',
                'amount' => $amount,
                'due_date' => $start->copy()->addWeeks($endWeek)->toDateString(),
                'status' => 'pending',
                'week_number' => $endWeek,
                'period_index' => $period,
                'notes' => "Период {$period} (нед. {$startWeek}–{$endWeek})",
            ]);
        }
    }

    private function generateMaintenanceSchedule(
        RentalClient $client,
        RentalClientVehicle $vehicle,
        Carbon $start,
        int $totalWeeks,
    ): void {
        $serviceEvery = (int) config('rent_buyout.maintenance.service_interval_weeks', 48);
        $oilEvery = (int) config('rent_buyout.maintenance.oil_change_interval_weeks', 16);

        for ($week = $serviceEvery; $week <= $totalWeeks; $week += $serviceEvery) {
            $client->maintenances()->create([
                'rental_client_vehicle_id' => $vehicle->id,
                'type' => 'service',
                'title' => 'Плановое ТО',
                'scheduled_at' => $start->copy()->addWeeks($week)->toDateString(),
                'status' => 'planned',
                'notes' => "Автоматически: неделя {$week}",
            ]);
        }

        for ($week = $oilEvery; $week <= $totalWeeks; $week += $oilEvery) {
            $client->maintenances()->create([
                'rental_client_vehicle_id' => $vehicle->id,
                'type' => 'oil_change',
                'title' => 'Замена масла',
                'scheduled_at' => $start->copy()->addWeeks($week)->toDateString(),
                'status' => 'planned',
                'notes' => "Автоматически: неделя {$week}",
            ]);
        }
    }

    private function generateContractNumber(RentalClient $client): string
    {
        return 'PK-'.now()->format('Y').'-'.str_pad((string) $client->id, 4, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3));
    }
}
