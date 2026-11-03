<?php

return [

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'version' => env('ANTHROPIC_VERSION', '2026-10-21'),
        'timeout' => 60,
    ],

    'models' => [
        'intake' => env('AI_INTAKE_MODEL', 'claude-haiku-5-5'),
        'brief' => env('AI_BRIEF_MODEL', 'claude-fable-5-1'),
    ],

    'intake' => [
        'max_tokens' => 1500,
        'effort' => 'low',
        'max_attempts' => 5,
    ],

    'brief' => [
        'max_tokens' => 16000,
        'effort' => 'medium',
    ],

    'embeddings' => [
        'key' => env('VOYAGE_API_KEY'),
        'base_url' => env('VOYAGE_BASE_URL', 'https://api.voyageai.com'),
        'model' => env('VOYAGE_MODEL', 'voyage-3.5'),
        'dimensions' => 1024,
    ],

    /*
    | USD per million tokens. Check against the provider pricing pages before
    | launch. Batch requests are billed at batch_multiplier of these rates.
    */
    'pricing' => [
        'claude-haiku-5-5' => ['input' => 0.10, 'output' => 0.50, 'cache_write' => 0.125, 'cache_read' => 0.01],
        'claude-fable-5-1' => ['input' => 10.00, 'output' => 50.00, 'cache_write' => 12.50, 'cache_read' => 0.25],
        'voyage-3.5' => ['input' => 0.06, 'output' => 0, 'cache_write' => 0, 'cache_read' => 0],
    ],

    'batch_multiplier' => 0.5,

    'spend' => [
        'monthly_cap_usd' => (float) env('AI_MONTHLY_CAP_USD', 50),
        // Intake keeps running past the cap up to cap times this ratio.
        'intake_ceiling_ratio' => 1.2,
    ],
];
