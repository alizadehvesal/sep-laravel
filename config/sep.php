<?php

declare(strict_types=1);

return [
    'terminal_id' => env('SEP_TERMINAL_ID'),

    'urls' => [
        'token' => env('SEP_TOKEN_URL', 'https://sep.shaparak.ir/onlinepg/onlinepg'),
        'payment' => env('SEP_PAYMENT_URL', 'https://sep.shaparak.ir/OnlinePG/OnlinePG'),
        'send_token' => env('SEP_SEND_TOKEN_URL', 'https://sep.shaparak.ir/OnlinePG/SendToken'),
        'verify' => env('SEP_VERIFY_URL', 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction'),
        'reverse' => env('SEP_REVERSE_URL', 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction'),
    ],

    'timeout' => (int) env('SEP_TIMEOUT', 15),
    'connect_timeout' => (int) env('SEP_CONNECT_TIMEOUT', 5),
    'verify_retries' => (int) env('SEP_VERIFY_RETRIES', 3),
    'verify_retry_delay_ms' => (int) env('SEP_VERIFY_RETRY_DELAY_MS', 1000),
    'token_expiry_minutes' => (int) env('SEP_TOKEN_EXPIRY_MINUTES', 20),
    'callback_method' => strtoupper((string) env('SEP_CALLBACK_METHOD', 'POST')),
    'route_prefix' => env('SEP_ROUTE_PREFIX', 'payment/sep'),
    'callback_route' => env('SEP_CALLBACK_ROUTE', 'payment/sep/callback'),
    'publish_routes' => (bool) env('SEP_PUBLISH_ROUTES', true),
    'middleware' => [],

    'database' => [
        'enabled' => (bool) env('SEP_DATABASE_ENABLED', true),
        'table' => env('SEP_PAYMENTS_TABLE', 'sep_payments'),
    ],

    'logging' => [
        'channel' => env('SEP_LOG_CHANNEL'),
        'redact_payloads' => true,
    ],

    'user_agent' => env('SEP_USER_AGENT', 'Vestra-Laravel-SEP/1.0'),
];
