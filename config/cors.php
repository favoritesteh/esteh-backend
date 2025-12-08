<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Di sini Anda dapat mengonfigurasi pengaturan Cross-Origin Resource Sharing (CORS).
    | Pengaturan ini digunakan oleh middleware `\Fruitcake\Cors\HandleCors`.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    // GANTI '*' dengan domain frontend Anda (wajib untuk credentials = true)
    'allowed_origins' => [
        'http://localhost:3000',             // development React/Vue/Nuxt
        'http://127.0.0.1:3000',
        'http://localhost:5173',             // Vite default
        'http://127.0.0.1:5173',
        'http://localhost:8081',
        'http://127.0.0.1:8081'              // alternatif
        // tambahkan domain lain jika diperlukan
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // WAJIB true untuk Sanctum stateful (cookie-based auth)
    'supports_credentials' => true,

];