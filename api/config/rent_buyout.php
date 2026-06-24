<?php

return [
    /*
    | Аренда с правом выкупа:
    | remainder = car_price - first_payment
    | yearly_overpayment = remainder * overpayment_rate
    | total_cost = car_price + (yearly_overpayment * term_years)
    | amount_to_finance = remainder + (yearly_overpayment * term_years)
    | total_weeks = term_years * weeks_per_year (52 — стандарт для финансовых расчётов)
    | total_periods = ceil(total_weeks / weeks_per_period)
    | period_payment = amount_to_finance / total_periods   ← основной платёж (каждые 4 нед.)
    | weekly_payment = period_payment / weeks_per_period   ← справочно
    */
    'default_overpayment_rate' => (float) env('RENT_BUYOUT_OVERPAYMENT_RATE', 0.40),

    'default_term_years' => (int) env('RENT_BUYOUT_TERM_YEARS', 3),

    /** Недель в одном платёжном периоде (4 недели — не календарный месяц). */
    'weeks_per_period' => (int) env('RENT_BUYOUT_WEEKS_PER_PERIOD', 4),

    /** Недель в году для графика платежей (52 — обычный календарный год). */
    'weeks_per_year' => (int) env('RENT_BUYOUT_WEEKS_PER_YEAR', 52),

    'maintenance' => [
        /** Плановое ТО — раз в N недель. */
        'service_interval_weeks' => (int) env('RENT_SERVICE_INTERVAL_WEEKS', 48),
        /** Замена масла — раз в N недель. */
        'oil_change_interval_weeks' => (int) env('RENT_OIL_CHANGE_INTERVAL_WEEKS', 16),
    ],

    'currency' => env('RENT_BUYOUT_CURRENCY', 'USD'),
];
