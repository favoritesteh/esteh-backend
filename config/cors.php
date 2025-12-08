<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // PAKAI WILDCARD BIAR GA ERROR LAGI
    'allowed_origins_patterns' => [
        '^http?://(localhost|127\.0\.0\.1)(:\d+)?$',
    ],

    'allowed_origins' => [], // kosongin

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];