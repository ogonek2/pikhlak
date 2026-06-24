<?php

namespace App\Services\Rental;

/**
 * Калькулятор аренды с правом выкупа (отдельная формула, без привязки к ИИ).
 *
 * Пример: авто 10 000 $, взнос 2 000 $, остаток 8 000 $, переплата 40%/год = 3 200 $/год.
 * За 3 года: 3 200 × 3 = 9 600 $ → итого 19 600 $.
 */
class RentBuyoutCalculator
{
    /**
     * @param  array{
     *     car_price: float|int|string,
     *     first_payment: float|int|string,
     *     term_years: int,
     *     overpayment_rate?: float|int|string|null,
     *     weeks_per_year?: int|null,
     *     weeks_per_period?: int|null,
     *     currency?: string|null,
     * }  $input
     * @return array<string, mixed>
     */
    public function calculate(array $input): array
    {
        $carPrice = round((float) $input['car_price'], 2);
        $firstPayment = round((float) $input['first_payment'], 2);
        $termYears = max(1, (int) $input['term_years']);
        $overpaymentRate = isset($input['overpayment_rate'])
            ? (float) $input['overpayment_rate']
            : (float) config('rent_buyout.default_overpayment_rate', 0.40);
        $weeksPerYear = (int) ($input['weeks_per_year'] ?? config('rent_buyout.weeks_per_year', 52));
        $weeksPerPeriod = (int) ($input['weeks_per_period'] ?? config('rent_buyout.weeks_per_period', 4));
        $currency = strtoupper((string) ($input['currency'] ?? config('rent_buyout.currency', 'USD')));

        if ($carPrice <= 0) {
            throw new \InvalidArgumentException('Цена автомобиля должна быть больше нуля.');
        }

        if ($firstPayment < 0 || $firstPayment >= $carPrice) {
            throw new \InvalidArgumentException('Первый взнос должен быть от 0 до цены авто.');
        }

        $remainder = round($carPrice - $firstPayment, 2);
        $yearlyOverpayment = round($remainder * $overpaymentRate, 2);
        $totalOverpayment = round($yearlyOverpayment * $termYears, 2);
        $totalCost = round($carPrice + $totalOverpayment, 2);
        $amountToFinance = round($remainder + $totalOverpayment, 2);
        $totalWeeks = $termYears * $weeksPerYear;
        $totalPeriods = $weeksPerPeriod > 0 ? (int) ceil($totalWeeks / $weeksPerPeriod) : 0;
        $periodPayment = $totalPeriods > 0 ? round($amountToFinance / $totalPeriods, 2) : 0.0;
        $weeklyPayment = $weeksPerPeriod > 0 ? round($periodPayment / $weeksPerPeriod, 2) : 0.0;
        $yearlyFinance = round($yearlyOverpayment + ($remainder / $termYears), 2);

        return [
            'car_price' => $carPrice,
            'first_payment' => $firstPayment,
            'remainder' => $remainder,
            'term_years' => $termYears,
            'term_weeks' => $totalWeeks,
            'overpayment_rate' => $overpaymentRate,
            'overpayment_rate_percent' => round($overpaymentRate * 100, 1),
            'yearly_overpayment' => $yearlyOverpayment,
            'total_overpayment' => $totalOverpayment,
            'total_cost' => $totalCost,
            'amount_to_finance' => $amountToFinance,
            'yearly_finance_total' => $yearlyFinance,
            'total_weeks' => $totalWeeks,
            'total_periods' => $totalPeriods,
            'weeks_per_year' => $weeksPerYear,
            'weeks_per_period' => $weeksPerPeriod,
            'weekly_payment' => $weeklyPayment,
            'period_payment' => $periodPayment,
            'currency' => $currency,
        ];
    }

    /** Клиентский вывод (бот) — без деталей формулы и процентов. */
    public function formatTelegramMessage(array $result, ?string $carTitle = null): string
    {
        return $this->formatPublicMessage($result, $carTitle);
    }

    /** @param  array<string, mixed>  $result */
    public function formatPublicMessage(array $result, ?string $carTitle = null): string
    {
        $sym = config('client_bot.currency_symbols.'.$result['currency'], $result['currency']);
        $money = fn (float $n) => number_format($n, 0, '.', ' ').' '.$sym;
        $carLine = $carTitle ? "<b>{$carTitle}</b>\n" : '';
        $years = (int) $result['term_years'];
        $yearLabel = $years === 1 ? 'год' : ($years < 5 ? 'года' : 'лет');

        return "📊 <b>Расчёт аренды с правом выкупа</b>\n\n"
            .$carLine
            .'Стоимость авто: <b>'.$money((float) $result['car_price'])."</b>\n"
            .'Первый взнос: <b>'.$money((float) $result['first_payment'])."</b>\n"
            .'Срок: <b>'.$years.'</b> '.$yearLabel."\n\n"
            .'Итого за срок: <b>'.$money((float) $result['total_cost'])."</b>\n"
            .'Платёж каждые <b>'.$result['weeks_per_period'].' недели</b>: <b>'.$money((float) $result['period_payment'])."</b>\n\n"
            .'<i>Расчёт ориентировочный. Точные условия — у менеджера.</i>';
    }

    /** Краткая строка для админ-превью (без процентов и остатка). */
    public function formatAdminSummary(array $result): string
    {
        $sym = config('client_bot.currency_symbols.'.$result['currency'], $result['currency']);
        $money = fn (float $n) => number_format($n, 0, '.', ' ').' '.$sym;
        $years = (int) $result['term_years'];
        $yearLabel = $years === 1 ? 'год' : ($years < 5 ? 'года' : 'лет');

        return "Срок {$years} {$yearLabel}, итого {$money((float) $result['total_cost'])}, "
            ."платёж {$money((float) $result['period_payment'])} / {$result['weeks_per_period']} нед.";
    }
}
