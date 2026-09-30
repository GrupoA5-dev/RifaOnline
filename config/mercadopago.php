<?php

return [
    'base_url' => env('MERCADOPAGO_BASE_URL', 'https://api.mercadopago.com'),
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    'notification_url' => env('MERCADOPAGO_NOTIFICATION_URL'),
    'timeout' => (int) env('MERCADOPAGO_TIMEOUT', 15),
    'connect_timeout' => (int) env('MERCADOPAGO_CONNECT_TIMEOUT', 5),
];
