<?php

return [
    /*
    | Если true — при отсутствии ключей синхронизация пишет демо-метрики (для разработки UI).
    | В production выключите и подключайте только реальные API.
    */
    'demo_when_unconfigured' => env('ANALYTICS_DEMO_MODE', true),

    'sync_default_days' => (int) env('ANALYTICS_SYNC_DAYS', 30),

    'connection_statuses' => [
        'disconnected' => 'Не подключён',
        'configured' => 'Ключи сохранены',
        'connected' => 'Синхронизация OK',
        'error' => 'Ошибка API',
    ],

    'platforms' => [
        'meta' => [
            'name' => 'Meta (Facebook / Instagram)',
            'apis' => ['Marketing API', 'Graph API'],
            'docs_url' => 'https://developers.facebook.com/docs/marketing-api/',
            'collector' => 'meta',
            'credential_fields' => [
                ['key' => 'access_token', 'label' => 'Access Token (долгоживущий)', 'type' => 'password', 'required' => true],
                ['key' => 'ad_account_id', 'label' => 'Ad Account ID (act_…)', 'type' => 'text', 'required' => true],
                ['key' => 'page_id', 'label' => 'Facebook Page ID', 'type' => 'text', 'required' => false],
                ['key' => 'instagram_business_id', 'label' => 'Instagram Business Account ID', 'type' => 'text', 'required' => false],
            ],
            'metrics_paid' => [
                'campaigns' => 'Статистика рекламных кампаний',
                'spend' => 'Расходы',
                'impressions' => 'Показы',
                'clicks' => 'Клики',
                'leads' => 'Лиды',
                'applications' => 'Заявки (lead forms)',
            ],
            'metrics_organic' => [
                'page_insights' => 'Статистика страницы',
                'account_reach' => 'Охват аккаунта',
                'followers' => 'Подписчики',
            ],
            'notes' => 'Instagram Business подключается через тот же Meta Graph API (instagram_business_id).',
        ],

        'instagram' => [
            'name' => 'Instagram',
            'apis' => ['Meta Graph API (Instagram Business)'],
            'docs_url' => 'https://developers.facebook.com/docs/instagram-api/',
            'collector' => 'meta',
            'inherits' => 'meta',
            'credential_fields' => [
                ['key' => 'access_token', 'label' => 'Meta Access Token', 'type' => 'password', 'required' => true],
                ['key' => 'instagram_business_id', 'label' => 'Instagram Business Account ID', 'type' => 'text', 'required' => true],
            ],
            'metrics_paid' => [],
            'metrics_organic' => [
                'impressions' => 'Показы',
                'reach' => 'Охват',
                'followers' => 'Подписчики',
                'likes' => 'Лайки',
                'comments' => 'Комментарии',
            ],
            'notes' => 'Платная реклама Instagram учитывается в канале Meta. Здесь — органика и профиль.',
        ],

        'tiktok' => [
            'name' => 'TikTok',
            'apis' => ['TikTok Marketing API', 'TikTok Organic (ограниченно)'],
            'docs_url' => 'https://business-api.tiktok.com/portal/docs',
            'collector' => 'tiktok',
            'credential_fields' => [
                ['key' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => true],
                ['key' => 'advertiser_id', 'label' => 'Advertiser ID', 'type' => 'text', 'required' => true],
                ['key' => 'app_id', 'label' => 'App ID', 'type' => 'text', 'required' => false],
            ],
            'metrics_paid' => [
                'campaigns' => 'Кампании',
                'ads' => 'Объявления',
                'spend' => 'Расходы',
                'impressions' => 'Показы',
                'clicks' => 'Клики',
                'leads' => 'Лиды',
            ],
            'metrics_organic' => [
                'views' => 'Просмотры',
                'likes' => 'Лайки',
                'comments' => 'Комментарии',
                'followers' => 'Подписчики',
            ],
            'notes' => 'Органика TikTok доступна ограниченно — зависит от типа аккаунта и разрешений API.',
        ],

        'youtube' => [
            'name' => 'YouTube',
            'apis' => ['YouTube Analytics API', 'YouTube Data API'],
            'docs_url' => 'https://developers.google.com/youtube/analytics',
            'collector' => 'youtube',
            'credential_fields' => [
                ['key' => 'client_id', 'label' => 'OAuth Client ID', 'type' => 'text', 'required' => true],
                ['key' => 'client_secret', 'label' => 'OAuth Client Secret', 'type' => 'password', 'required' => true],
                ['key' => 'refresh_token', 'label' => 'Refresh Token', 'type' => 'password', 'required' => true],
                ['key' => 'channel_id', 'label' => 'Channel ID', 'type' => 'text', 'required' => true],
            ],
            'metrics_paid' => [],
            'metrics_organic' => [
                'views' => 'Просмотры',
                'watch_time' => 'Время просмотра',
                'retention' => 'Удержание аудитории',
                'subscribers' => 'Подписчики',
                'traffic_sources' => 'Источники трафика',
                'video_stats' => 'Статистика по видео',
            ],
            'notes' => 'Для рекламы Google Ads — отдельная интеграция (позже). Сейчас — канал и аналитика видео.',
        ],

        'olx' => [
            'name' => 'OLX',
            'apis' => ['OLX Partner API (зависит от страны)'],
            'docs_url' => 'https://developer.olx.pl/',
            'collector' => 'olx',
            'credential_fields' => [
                ['key' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
                ['key' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
                ['key' => 'country_code', 'label' => 'Код страны (ua, pl…)', 'type' => 'text', 'required' => true],
                ['key' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => false],
            ],
            'metrics_paid' => [],
            'metrics_organic' => [
                'listings' => 'Объявления',
                'listing_views' => 'Просмотры объявлений',
                'messages' => 'Сообщения / лиды',
                'listing_stats' => 'Статистика объявлений',
            ],
            'notes' => 'Официальный API есть не для всех рынков. Доступность объявлений и сообщений зависит от страны.',
        ],
    ],
];
