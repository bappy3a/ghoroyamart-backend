<?php

$configuredOrigins = explode(',', (string) env(
    'CORS_ALLOWED_ORIGINS',
    'http://localhost:3000,http://127.0.0.1:3000,http://agonito.test,http://localhost:8000'
));

$allowedOrigins = array_values(array_unique(array_filter(array_map(
    'trim',
    [
        ...$configuredOrigins,
        (string) env('FRONTEND_URL'),
        'https://www.agonito.com',
        'https://agonito.com',
        'https://agonito.store',
        'https://www.agonito.store',
    ]
))));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Storefront (Next.js) calls this API from the browser. Keep api/* open to
    | local/dev frontends; tighten allowed_origins in production as needed.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
        '#^https://.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 60 * 60 * 24,

    'supports_credentials' => false,

];
