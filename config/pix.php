<?php

return [
    'mode' => env('PIX_MODE', 'static'),
    'key' => env('PIX_KEY'),
    'merchant_name' => env('PIX_MERCHANT_NAME'),
    'merchant_city' => env('PIX_MERCHANT_CITY'),
    'txid_prefix' => env('PIX_TXID_PREFIX', 'A5'),
];
