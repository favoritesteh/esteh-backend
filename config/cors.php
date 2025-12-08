<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://esteh-backend-production.up.railway.app',  // Backend domain
        'http://localhost:5173',                            // Vite local (HTTP)
        'http://127.0.0.1:5173',
        'http://localhost:8081',                            // Additional local port
        'http://127.0.0.1:8081',
        // Add production frontend domains here when deployed (e.g., 'https://esteh-frontend.vercel.app')
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,  // Required for cookie-based authentication
];