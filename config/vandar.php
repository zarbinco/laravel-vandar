<?php

declare(strict_types=1);

return [
    'business' => env('VANDAR_BUSINESS'),

    'tokens' => [
        'access_token' => env('VANDAR_ACCESS_TOKEN'),
        'refresh_token' => env('VANDAR_REFRESH_TOKEN'),

        // Supported token stores: config, cache, custom.
        'store' => env('VANDAR_TOKEN_STORE', 'cache'),
        'cache_key' => env('VANDAR_TOKEN_CACHE_KEY', 'vandar.tokens'),

        // Used by cache store and refresh scheduling helpers.
        'access_token_ttl_seconds' => (int) env('VANDAR_ACCESS_TOKEN_TTL_SECONDS', 432000),
        'refresh_token_ttl_seconds' => (int) env('VANDAR_REFRESH_TOKEN_TTL_SECONDS', 864000),

        // Refresh before actual expiration to avoid edge cases.
        'refresh_before_expiration_seconds' => (int) env('VANDAR_REFRESH_BEFORE_EXPIRATION_SECONDS', 3600),

        // Use Laravel cache lock when available to avoid concurrent refresh.
        'lock_key' => env('VANDAR_TOKEN_LOCK_KEY', 'vandar.tokens.refresh.lock'),
        'lock_seconds' => (int) env('VANDAR_TOKEN_LOCK_SECONDS', 30),
        'lock_wait_seconds' => (int) env('VANDAR_TOKEN_LOCK_WAIT_SECONDS', 5),
        'refresh_attempts' => (int) env('VANDAR_TOKEN_REFRESH_ATTEMPTS', 3),
        'refresh_retry_sleep_ms' => (int) env('VANDAR_TOKEN_REFRESH_RETRY_SLEEP_MS', 250),

        // Cache driver stores encrypted payload by default.
        'encrypt_cache' => env('VANDAR_TOKEN_ENCRYPT_CACHE', true),
    ],

    'base_urls' => [
        'api' => env('VANDAR_API_URL', 'https://api.vandar.io'),
        'ipg' => env('VANDAR_IPG_URL', 'https://ipg.vandar.io'),
        'batch' => env('VANDAR_BATCH_URL', 'https://batch.vandar.io'),
        'subscription' => env('VANDAR_SUBSCRIPTION_URL', 'https://subscription.vandar.io'),
    ],

    'ipg' => [
        'api_key' => env('VANDAR_IPG_API_KEY'),
        'callback_url' => env('VANDAR_IPG_CALLBACK_URL'),
    ],

    'http' => [
        'timeout' => (int) env('VANDAR_HTTP_TIMEOUT', 20),
        'connect_timeout' => (int) env('VANDAR_HTTP_CONNECT_TIMEOUT', 10),
        'verify_ssl' => env('VANDAR_HTTP_VERIFY_SSL', true),

        // Retry must remain conservative.
        // Never automatically retry unsafe money-moving POST endpoints.
        'retry' => [
            'enabled' => env('VANDAR_HTTP_RETRY', false),
            'times' => (int) env('VANDAR_HTTP_RETRY_TIMES', 1),
            'sleep_ms' => (int) env('VANDAR_HTTP_RETRY_SLEEP_MS', 500),
        ],
    ],

    'rate_limit' => [
        'aware' => env('VANDAR_RATE_LIMIT_AWARE', true),
        'respect_retry_after' => env('VANDAR_RESPECT_RETRY_AFTER', true),
        'max_retry_after_seconds' => (int) env('VANDAR_MAX_RETRY_AFTER_SECONDS', 3),
        'retry_safe_methods' => env('VANDAR_RETRY_SAFE_METHODS', true),
        'retry_money_moving_requests' => env('VANDAR_RETRY_MONEY_MOVING_REQUESTS', false),
    ],

    'logging' => [
        'enabled' => env('VANDAR_LOGGING_ENABLED', false),

        // Defensive best-effort masking for package logs; do not log raw sensitive API responses in production.
        'redact_sensitive_data' => true,
    ],
];
