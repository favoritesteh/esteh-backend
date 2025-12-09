<?php

return [
    'paths' => ['api/*', '*'],  // FIX UTAMA

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        '*',  // Kalau pakai Railway lebih aman buka dulu
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
