<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Emporia account
    |--------------------------------------------------------------------------
    |
    | The email address and password of your Emporia Energy account. They are
    | only used to log in when no valid (cached) tokens are available.
    |
    */

    'username' => env('EMPORIA_USERNAME'),

    'password' => env('EMPORIA_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Existing tokens (optional)
    |--------------------------------------------------------------------------
    |
    | Start from previously obtained Cognito tokens instead of a password.
    |
    */

    'id_token' => env('EMPORIA_ID_TOKEN'),

    'access_token' => env('EMPORIA_ACCESS_TOKEN'),

    'refresh_token' => env('EMPORIA_REFRESH_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Token cache
    |--------------------------------------------------------------------------
    |
    | Tokens (including the refresh token) are stored in this cache store so
    | the client does not log in on every request. Set "store" to null to use
    | the default store, or disable caching entirely with "enabled" => false.
    |
    */

    'cache' => [
        'enabled' => env('EMPORIA_CACHE_ENABLED', true),
        'store' => env('EMPORIA_CACHE_STORE'),
        'key' => env('EMPORIA_CACHE_KEY'),
        'ttl' => (int) env('EMPORIA_CACHE_TTL', 2592000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication flow
    |--------------------------------------------------------------------------
    |
    | "srp" (Secure Remote Password, what the Emporia app and PyEmVue use) or
    | "password" (plain USER_PASSWORD_AUTH).
    |
    */

    'auth_flow' => env('EMPORIA_AUTH_FLOW', 'srp'),

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    */

    'connect_timeout' => (float) env('EMPORIA_CONNECT_TIMEOUT', 6.03),

    'read_timeout' => (float) env('EMPORIA_READ_TIMEOUT', 10.03),

    'token_refresh_leeway' => 60,

    'retry' => [
        'max_attempts' => 5,
        'initial_delay_ms' => 500,
        'max_delay_ms' => 30000,
    ],

];
