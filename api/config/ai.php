<?php

return [
    'default_provider' => env('AI_PROVIDER', 'groq'),
    'verify_ssl' => env('AI_VERIFY_SSL', env('APP_ENV') === 'local' ? false : true),

    'providers' => [
        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'base_url' => 'https://api.groq.com/openai/v1',
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            // Запасные модели при rate limit (отдельные лимиты на groq.com)
            'fallback_models' => array_filter(array_map('trim', explode(',', env('GROQ_FALLBACK_MODELS', 'llama-3.1-8b-instant,gemma2-9b-it')))),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'fallback_models' => array_filter(array_map('trim', explode(',', env('GEMINI_FALLBACK_MODELS', 'gemini-2.0-flash-lite,gemini-1.5-flash')))),
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
    ],

    'provider_labels' => [
        'groq' => 'Groq (быстрый, бесплатный лимит)',
        'gemini' => 'Google Gemini',
        'openai' => 'OpenAI',
    ],

    'behavior_defaults' => [
        'auto_reply' => true,
        'use_faq_first' => true,
        'use_project_data' => true,
        'faq_match_threshold' => 0.45,
        'max_context_messages' => 12,
        'enable_warming' => true,
        'hot_lead_threshold' => 70,
        'strict_allowed_topics' => false,
        'warming_aggressiveness' => 'medium',
        'disable_ai_on_operator_request' => true,
    ],

    'rule_types' => [
        'company_fact' => 'Факт о компании (всегда важно)',
        'constraint' => 'Запрет / ограничение',
        'correction' => 'Исправление ложных утверждений',
        'instruction' => 'Инструкция поведения',
        'example' => 'Пример ответа',
    ],

    'rule_presets' => [
        'not_leasing' => [
            'name' => 'Мы не лизинговая компания',
            'type' => 'correction',
            'priority' => 100,
            'always' => true,
            'keywords' => 'лизинг,аренда авто,leasing,рассрочка от банка,арендовать авто',
            'instruction' => 'Pikhlak Auto — это импорт и продажа автомобилей под ключ из США/Европы (подбор, доставка, растаможка). Мы НЕ лизинговая компания и НЕ сдаём авто в аренду. Если спрашивают про лизинг — вежливо объясни это и предложи покупку/доставку авто из каталога.',
        ],
        'company_services' => [
            'name' => 'Чем занимаемся',
            'type' => 'company_fact',
            'priority' => 95,
            'always' => true,
            'keywords' => '',
            'instruction' => 'Мы помогаем: подобрать авто, купить на аукционе/у дилера, доставить и растаможить, сопровождение сделки. Не обещай услуги, которых нет в FAQ и каталоге.',
        ],
        'no_fake_prices' => [
            'name' => 'Не выдумывать цены',
            'type' => 'constraint',
            'priority' => 90,
            'always' => true,
            'keywords' => '',
            'instruction' => 'Никогда не называй точную стоимость доставки/растаможки без данных из FAQ. Цены авто — только из каталога по ID.',
        ],
        'leasing_questions' => [
            'name' => 'Вопросы про лизинг (по ключевым словам)',
            'type' => 'correction',
            'priority' => 85,
            'always' => false,
            'keywords' => 'лизинг,аренда,leasing,в аренду,арендовать',
            'instruction' => 'Клиент спрашивает про лизинг/аренду. Чётко скажи: Pikhlak не лизинг, мы продаём и доставляем авто в собственность. Предложи варианты из каталога или подбор под бюджет.',
        ],
    ],

    'route_presets' => [
        'leasing_clarify' => [
            'name' => 'Уточнение: не лизинг',
            'slug' => 'not_leasing',
            'intent_keywords' => 'лизинг,аренда,leasing,в аренду,арендовать,рассрочка лизинг',
            'extra_instruction' => 'Клиент думает, что мы лизинг. Объясни: Pikhlak Auto = покупка + доставка авто под ключ. Не лизинг. Предложи каталог или менеджера.',
            'priority' => 95,
            'data_sources' => ['cars', 'faq'],
        ],
        'company_about' => [
            'name' => 'О компании / услуги',
            'slug' => 'about_company',
            'intent_keywords' => 'кто вы,что за компания,чем занимаетесь,услуги,сервис,pikhlak,пихлак',
            'extra_instruction' => 'Кратко опиши Pikhlak Auto: импорт авто, подбор, доставка, растаможка. Не лизинг. Только факты из данных проекта.',
            'priority' => 75,
            'data_sources' => ['faq', 'cars'],
        ],
    ],
];
