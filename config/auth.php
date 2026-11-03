<?php

use App\Models\Citizen;
use App\Models\User;

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    | web: leaders, leader staff and admins, through Fortify with two factors.
    | citizen: residents, signed in with a phone number and an SMS code.
    */
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'citizen' => [
            'driver' => 'session',
            'provider' => 'citizens',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],
        'citizens' => [
            'driver' => 'eloquent',
            'model' => Citizen::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    // Separate from APP_KEY so either can be rotated without breaking the other.
    'identity_hash_key' => env('IDENTITY_HASH_KEY'),
];
