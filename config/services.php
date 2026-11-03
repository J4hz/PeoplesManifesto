<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'africastalking' => [
        // "sandbox" while testing against the Africa's Talking simulator.
        'username' => env('AT_USERNAME', 'sandbox'),
        'api_key' => env('AT_API_KEY'),
        'short_code' => env('AT_SHORT_CODE'),
        'sender_id' => env('AT_SENDER_ID'),
        'sandbox' => (bool) env('AT_SANDBOX', true),
        // Africa's Talking does not sign callbacks. This token is part of the callback URL.
        'webhook_token' => env('AT_WEBHOOK_TOKEN'),
        // Comma separated. Leave empty to skip the IP check.
        'webhook_ips' => array_filter(explode(',', (string) env('AT_WEBHOOK_IPS', ''))),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),
    ],

];
