<?php

return [
    'channels' => [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'telegram' => 'Telegram',
        'google' => 'Google / поиск',
        'avito' => 'Avito / доски',
        'offline' => 'Офлайн / визитка',
        'partner_site' => 'Сайт партнёра',
        'other' => 'Другое',
    ],

    'types' => [
        'traffic' => 'Канал трафика (соцсеть / воронка)',
        'car' => 'Конкретное авто',
        'partner' => 'Посредник / реферал',
    ],

    'default_settings' => [
        'auto_show_car' => true,
        'pin_car_in_context' => true,
        'assign_warming_bonus' => 5,
        'notify_new_lead' => false,
        'tags' => [],
    ],
];
