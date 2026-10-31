# Configuration

The configuration file is located at `config/snapppay.php`.

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => env('SNAPP_PAY_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | SnappPay Connections
    |--------------------------------------------------------------------------
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
    */
    'cache_token' => (bool) env('SNAPP_PAY_CACHE_TOKEN', false),
    'cache_store' => env('SNAPP_PAY_CACHE_STORE'),
    'cache_prefix' => env('SNAPP_PAY_CACHE_PREFIX', 'snapppay_token_'),

    /*
    |--------------------------------------------------------------------------
    | Default Commission Type
    |--------------------------------------------------------------------------
    */
    'default_commission_type' => 100,

    /*
    |--------------------------------------------------------------------------
    | Resilience Settings
    |--------------------------------------------------------------------------
    */
    'resilience' => [
        'verify_timeout' => 30,
        'max_retries' => 2,
        'retry_delay_ms' => 500,
    ],
];
```

## Configuration Options

### `connections`
Allows defining multiple merchant accounts. Each connection specifies:
- `base_url`: SnappPay endpoint URL.
- `client_id` & `client_secret`: Basic authentication credentials.
- `username` & `password`: Merchant operator credentials for password grant.
- `timeout`: Maximum waiting time in seconds for HTTP responses (default: 30).
- `connect_timeout`: Maximum socket connection time in seconds (default: 10).

### `cache_token`
- **Default**: `false`.
- The SnappPay documentation recommends allowing access tokens to expire naturally.
- When set to `true`, the token is stored in your Laravel cache repository (e.g. Redis, Memcached, database) with an expiry TTL of `expires_in - 60s`.

### `default_commission_type`
- **Default**: `100`.
- As specified in the SnappPay documentation: if your product catalog does not utilize diverse product categorization, pass `100` as the default `commissionType`.

### `resilience`
- `max_retries`: Number of attempts to re-query status or retry Verify/Settle upon timeout or network drops (default: `2`).
- `retry_delay_ms`: Pause time in milliseconds between retry attempts (default: `500`).
