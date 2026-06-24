<?php

return [
    /*
    | CRM API — источник данных при синхронизации. Всё сохраняется в локальную БД;
    | бот, уведомления и админка работают только с rental_* таблицами.
    */
    'crm' => [
        'demo_mode' => env('CLIENT_BOT_CRM_DEMO', true),
        'base_url' => env('CLIENT_BOT_CRM_URL'),
        'api_token' => env('CLIENT_BOT_CRM_TOKEN'),
        'timeout' => (int) env('CLIENT_BOT_CRM_TIMEOUT', 15),
        'clients_endpoint' => '/clients',
    ],

    /*
    | Ожидаемый формат записи CRM (плоский или с массивами payments/maintenances/insurances).
    | Минимум: client_id, name, phone, car, contract_number, payment_date, payment_amount,
    | service_type, service_date.
    */
    'crm_snapshot_fields' => [
        'client_id', 'name', 'phone', 'email', 'status', 'car', 'plate_number', 'vin', 'mileage',
        'contract_number', 'contract_start', 'contract_end', 'monthly_amount', 'total_amount', 'currency',
        'payment_date', 'payment_amount', 'payment_status', 'service_type', 'service_date',
        'insurance_provider', 'insurance_valid_until',
        'payments', 'maintenances', 'insurances',
    ],

    'scheduler_interval_minutes' => (int) env('CLIENT_BOT_SCHEDULER_MINUTES', 5),

    /*
    | Смещения в днях относительно даты события (отрицательные — заранее, положительные — просрочка).
    */
    'default_notification_offsets' => [-5, -3, -1, 0, 1, 3],

    'event_types' => [
        'payment' => 'Платёж по договору',
        'maintenance' => 'ТО / сервис',
        'oil_change' => 'Замена масла',
        'inspection' => 'Техосмотр',
        'insurance' => 'Страховка',
        'other' => 'Прочие работы',
    ],

    'maintenance_type_map' => [
        'service' => 'maintenance',
        'to' => 'maintenance',
        'maintenance' => 'maintenance',
        'oil' => 'oil_change',
        'oil_change' => 'oil_change',
        'inspection' => 'inspection',
        'other' => 'other',
    ],

    'currency_symbols' => [
        'UAH' => 'грн',
        'USD' => '$',
        'EUR' => '€',
    ],

    'liqpay' => [
        'enabled' => env('LIQPAY_ENABLED', false),
        'public_key' => env('LIQPAY_PUBLIC_KEY'),
        'private_key' => env('LIQPAY_PRIVATE_KEY'),
        'sandbox' => env('LIQPAY_SANDBOX', true),
    ],

    'payment_url_base' => env('CLIENT_BOT_PAYMENT_URL', null),

    'self_service_phrases' => [
        'balance' => ['сколько осталось', 'остаток', 'сколько платить', 'баланс', 'задолженность'],
        'next_payment' => ['следующий платеж', 'когда платить', 'дата платежа', 'сумма платежа'],
        'next_service' => ['когда то', 'следующее то', 'техобслуживание', 'сервис', 'то авто'],
        'insurance' => ['страховка', 'страхование', 'полис'],
        'car' => ['какой автомобиль', 'какая машина', 'моё авто', 'мое авто', 'какая у меня машина', 'какой у меня автомобиль'],
    ],
];
