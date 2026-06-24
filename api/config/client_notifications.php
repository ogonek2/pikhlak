<?php

/**
 * Шаблоны уведомлений клиенту в Telegram.
 *
 * Плейсхолдеры: {first_name}, {full_name}, {amount}, {due_date}, {currency},
 * {car}, {contract_number}, {date}, {title}, {provider}, {payment_url}, {offset_label}, {payment_prefix}
 *
 * attachments: invoice — PDF счёта и QR (для платежей).
 */
return [
    'types' => [
        'payment.created' => [
            'label' => 'Новый платёж (админ)',
            'template' => "Добрый день, {first_name}.\n\n"
                ."Вам выставлен платёж по договору аренды.\n"
                ."Срок оплаты: <b>{due_date}</b>\n"
                ."Сумма: <b>{amount} {currency}</b>."
                ."{payment_url_block}",
            'attachments' => ['invoice'],
        ],

        'payment.updated' => [
            'label' => 'Платёж изменён (админ)',
            'template' => "Добрый день, {first_name}.\n\n"
                ."Обновлены данные платежа по договору аренды.\n"
                ."Срок оплаты: <b>{due_date}</b>\n"
                ."Сумма: <b>{amount} {currency}</b>\n"
                ."Статус: <b>{payment_status}</b>."
                ."{payment_url_block}",
            'attachments' => ['invoice'],
        ],

        'payment.paid' => [
            'label' => 'Платёж отмечен оплаченным',
            'template' => "Добрый день, {first_name}.\n\n"
                ."Платёж на сумму <b>{amount} {currency}</b> (срок {due_date}) отмечен как <b>оплаченный</b>.\n"
                .'Спасибо!',
        ],

        'profile.updated' => [
            'label' => 'Профиль обновлён (админ)',
            'template' => "Добрый день, {first_name}.\n\n"
                .'Данные вашего профиля в системе Pikhlak обновлены администратором.',
        ],

        'contract.created' => [
            'label' => 'Новый договор',
            'template' => "Добрый день, {first_name}.\n\n"
                ."Оформлен договор аренды{contract_number_block}.\n"
                .'Платёж за 4 недели: <b>{monthly_amount} {currency}</b>.',
        ],

        'contract.updated' => [
            'label' => 'Договор изменён',
            'template' => "Добрый день, {first_name}.\n\n"
                ."Обновлены условия договора аренды{contract_number_block}.\n"
                .'Платёж за 4 недели: <b>{monthly_amount} {currency}</b>.',
        ],

        'vehicle.created' => [
            'label' => 'Добавлен автомобиль',
            'template' => "Добрый день, {first_name}.\n\n"
                .'В ваш профиль добавлен автомобиль: <b>{car}</b>.',
        ],

        'vehicle.updated' => [
            'label' => 'Данные авто изменены',
            'template' => "Добрый день, {first_name}.\n\n"
                .'Обновлены данные автомобиля: <b>{car}</b>.',
        ],

        'maintenance.created' => [
            'label' => 'Запланировано ТО',
            'template' => "Добрый день, {first_name}.\n\n"
                .'Запланировано обслуживание: <b>{title}</b> для {car}.'."\n"
                .'Дата: <b>{date}</b>.',
        ],

        'maintenance.updated' => [
            'label' => 'ТО изменено',
            'template' => "Добрый день, {first_name}.\n\n"
                .'Обновлена запись обслуживания: <b>{title}</b> для {car}.\n'
                .'Дата: <b>{date}</b>.',
        ],

        'insurance.created' => [
            'label' => 'Добавлена страховка',
            'template' => "Добрый день, {first_name}.\n\n"
                .'В профиль добавлен полис страхования <b>{provider}</b>.\n'
                .'Действует до: <b>{date}</b>.',
        ],

        'insurance.updated' => [
            'label' => 'Страховка изменена',
            'template' => "Добрый день, {first_name}.\n\n"
                .'Обновлены данные страхового полиса <b>{provider}</b>.\n'
                .'Действует до: <b>{date}</b>.',
        ],

        'phone.added' => [
            'label' => 'Добавлен телефон',
            'template' => "Добрый день, {first_name}.\n\n"
                .'В ваш профиль добавлен контактный телефон: <b>{phone}</b>.',
        ],

        /* Планировщик (напоминания по смещению от даты события) */
        'payment.reminder' => [
            'label' => 'Напоминание о платеже',
            'template' => "Добрый день, {first_name}.\n\n"
                .'{payment_prefix}: <b>{due_date}</b> необходимо внести платёж по договору аренды{buyout_suffix}.\n'
                .'Сумма к оплате: <b>{amount} {currency}</b>.'
                .'{payment_url_block}',
            'attachments' => ['invoice'],
        ],

        'maintenance.reminder' => [
            'label' => 'Напоминание о ТО',
            'template' => "Добрый день, {first_name}.\n\n"
                .'{maintenance_prefix} <b>{title}</b> для {car} — <b>{date}</b>.',
        ],

        'insurance.reminder' => [
            'label' => 'Напоминание о страховке',
            'template' => "Добрый день, {first_name}.\n\n"
                .'Страховой полис ({provider}) действует до <b>{date}</b>. {insurance_suffix}',
        ],
    ],
];
