<?php

return [

    'default' => env('PAYMENT_PROVIDER', 'manual'),

    'currency' => env('PAYMENT_CURRENCY', 'XOF'),

    'providers' => [
        'manual' => [
            'driver' => 'manual',
        ],
        'stripe' => [
            'driver' => 'stripe',
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'paypal' => [
            'driver' => 'paypal',
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        ],
        'flutterwave' => [
            'driver' => 'flutterwave',
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        ],
        'lemonsqueezy' => [
            'driver' => 'lemonsqueezy',
            'api_key' => env('LEMON_SQUEEZY_API_KEY'),
            'store_id' => env('LEMON_SQUEEZY_STORE_ID'),
        ],
    ],

];
