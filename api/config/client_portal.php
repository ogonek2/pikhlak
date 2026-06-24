<?php

return [
  'demo_channels' => [
        [
            'slug' => 'meta',
            'name' => 'Meta (Facebook / Instagram)',
            'leads_min' => 2,
            'leads_max' => 9,
            'clicks_min' => 40,
            'clicks_max' => 120,
            'cpc' => 0.38,
            'revenue_per_lead' => 150,
        ],
        [
            'slug' => 'tiktok',
            'name' => 'TikTok',
            'leads_min' => 3,
            'leads_max' => 12,
            'clicks_min' => 60,
            'clicks_max' => 180,
            'cpc' => 0.22,
            'revenue_per_lead' => 95,
        ],
        [
            'slug' => 'youtube',
            'name' => 'YouTube',
            'leads_min' => 1,
            'leads_max' => 6,
            'clicks_min' => 25,
            'clicks_max' => 70,
            'cpc' => 0.55,
            'revenue_per_lead' => 180,
        ],
        [
            'slug' => 'instagram',
            'name' => 'Instagram',
            'leads_min' => 2,
            'leads_max' => 8,
            'clicks_min' => 35,
            'clicks_max' => 95,
            'cpc' => 0.41,
            'revenue_per_lead' => 130,
        ],
        [
            'slug' => 'olx',
            'name' => 'OLX',
            'leads_min' => 4,
            'leads_max' => 14,
            'clicks_min' => 50,
            'clicks_max' => 140,
            'cpc' => 0.15,
            'revenue_per_lead' => 75,
        ],
    ],

    'client_statuses' => [
        'active' => 'Активный',
        'paused' => 'Пауза',
        'completed' => 'Завершён',
        'archived' => 'Архив',
    ],

    'payment_statuses' => [
        'pending' => 'Ожидает',
        'paid' => 'Оплачен',
        'overdue' => 'Просрочен',
        'cancelled' => 'Отменён',
    ],

    'payment_types' => [
        'rent' => 'Аренда',
        'insurance' => 'Страховка',
        'service' => 'Сервис',
        'other' => 'Прочее',
    ],

    'contract_statuses' => [
        'active' => 'Активный',
        'completed' => 'Завершён',
        'cancelled' => 'Отменён',
    ],

    'maintenance_statuses' => [
        'planned' => 'Запланировано',
        'scheduled' => 'Назначено',
        'completed' => 'Выполнено',
        'cancelled' => 'Отменено',
    ],

    'maintenance_types' => [
        'service' => 'ТО / сервис',
        'oil_change' => 'Замена масла',
        'inspection' => 'Техосмотр',
        'other' => 'Прочее',
    ],
];
