<?php

return [
    'secret' => env('JWT_SECRET', env('APP_KEY')),
    'access_ttl' => (int) env('JWT_ACCESS_TTL', 3600),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 604800),
    'algo' => 'HS256',
];
