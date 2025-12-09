<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],

    'allowed_origins' => [
        'https://esteh-backend-production.up.railway.app',  
        'http://localhost:5173',                           
        'http://127.0.0.1:5173',
        'http://localhost:8081',                            
        'http://127.0.0.1:8081',
    ],
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, 
];