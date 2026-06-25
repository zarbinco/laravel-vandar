<?php

declare(strict_types=1);

return [
    'business' => env('VANDAR_BUSINESS'),

    'tokens' => [
        'access_token' => env('VANDAR_ACCESS_TOKEN'),
        'refresh_token' => env('VANDAR_REFRESH_TOKEN'),
        'store' => env('VANDAR_TOKEN_STORE', 'cache'),
        'cache_key' => env('VANDAR_TOKEN_CACHE_KEY', 'vandar.tokens'),
    ],

    'base_urls' => [
        'api' => env('VANDAR_API_URL', 'https://api.vandar.io'),
        'ipg' => env('VANDAR_IPG_URL', 'https://ipg.vandar.io'),
        'batch' => env('VANDAR_BATCH_URL', 'https://batch.vandar.io'),
        'subscription' => env('VANDAR_SUBSCRIPTION_URL', 'https://subscription.vandar.io'),
    ],

    'http' => [
        'timeout' => (int) env('VANDAR_HTTP_TIMEOUT', 20),
        'connect_timeout' => (int) env('VANDAR_HTTP_CONNECT_TIMEOUT', 10),
        'verify_ssl' => env('VANDAR_HTTP_VERIFY_SSL', true),
        'retry' => [
            'enabled' => env('VANDAR_HTTP_RETRY', false),
            'times' => (int) env('VANDAR_HTTP_RETRY_TIMES', 1),
            'sleep_ms' => (int) env('VANDAR_HTTP_RETRY_SLEEP_MS', 500),
        ],
    ],

    'logging' => [
        'enabled' => env('VANDAR_LOGGING_ENABLED', false),
        'redact_sensitive_data' => true,
    ],
];
