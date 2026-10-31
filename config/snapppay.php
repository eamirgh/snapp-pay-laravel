<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Define the default connection configuration key to use.
    |
    */
    'default' => env('SNAPP_PAY_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | SnappPay Connections
    |--------------------------------------------------------------------------
    |
    | Configure your SnappPay credentials. Supports multiple merchant accounts.
    |
    | Note: SnappPay provides Stage access initially. Production access is
    | granted after technical pre-demo & demo validation.
    | IP Whitelist is required by SnappPay for all outbound requests.
    |
    */
    'connections' => [
        'default' => [
            'base_url' => env('SNAPP_PAY_BASE_URL', 'https://api.snapppay.ir'),
            'client_id' => env('SNAPP_PAY_CLIENT_ID', ''),
            'client_secret' => env('SNAPP_PAY_CLIENT_SECRET', ''),
            'username' => env('SNAPP_PAY_USERNAME', ''),
            'password' => env('SNAPP_PAY_PASSWORD', ''),
            'timeout' => (int) env('SNAPP_PAY_TIMEOUT', 30),
            'connect_timeout' => (int) env('SNAPP_PAY_CONNECT_TIMEOUT', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Token Caching
    |--------------------------------------------------------------------------
    |
    | SnappPay documentation notes: "Do not cache JWT access token indefinitely;
    | allow it to expire properly."
    | If enabled, the token is cached with a safety margin before expiration
    | (expires_in - 120s) using your default Laravel cache store.
    |
    */
    'cache_token' => (bool) env('SNAPP_PAY_CACHE_TOKEN', false),
    'cache_store' => env('SNAPP_PAY_CACHE_STORE'),
    'cache_prefix' => env('SNAPP_PAY_CACHE_PREFIX', 'snapppay_token_'),

    /*
    |--------------------------------------------------------------------------
    | Default Commission Type
    |--------------------------------------------------------------------------
    |
    | If your catalog does not use diverse categorization, SnappPay requires
    | sending 100 as the default commissionType for cart items.
    |
    */
    'default_commission_type' => 100,

    /*
    |--------------------------------------------------------------------------
    | Resilience & Retry Settings
    |--------------------------------------------------------------------------
    |
    | SnappPay integration guidelines require checking status if verify or settle
    | times out or fails (Sections 11 & 12 of technical documentation).
    |
    */
    'resilience' => [
        'verify_timeout' => 30,
        'max_retries' => 2,
        'retry_delay_ms' => 500,
    ],
];
