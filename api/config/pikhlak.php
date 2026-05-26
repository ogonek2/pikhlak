<?php

return [
    'bot_hmac_secret' => env('PIKHLAK_BOT_HMAC_SECRET'),
    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'default_project_slug' => env('PIKHLAK_PROJECT_SLUG', 'pikhlak'),
];
