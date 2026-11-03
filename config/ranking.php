<?php

/*
| Weights for ordering issue clusters. Tune these without touching code.
| The score is a plain weighted sum, see App\Services\ClusterRanker.
*/

return [

    'weights' => [
        // Applied to log(1 + weighted distinct residents), so the 50th
        // resident still counts but cannot drown out severity.
        'residents' => 3.0,
        'severity' => 2.0,
        // Applied to the share of the ward's areas the issue has reached.
        'spread' => 1.5,
        // Applied to the growth ratio, capped below.
        'growth' => 1.0,
    ],

    'severity_points' => [
        'annoying' => 1.0,
        'serious' => 2.0,
        'urgent' => 3.0,
    ],

    // Urgent items in these categories weigh more because lives depend on them.
    'urgent_category_multiplier' => [
        'water' => 1.5,
        'health' => 1.5,
        'security' => 1.5,
    ],

    'growth' => [
        'window_days' => 7,
        'cap' => 3.0,
        'rising_above' => 1.25,
        'falling_below' => 0.75,
    ],

    'trust' => [
        // Accounts older than this get a light boost.
        'long_standing_days' => 180,
        'long_standing_weight' => 1.15,
        'new_account_days' => 7,
        // A burst is when many brand new accounts pile onto one issue in a short window.
        'burst_window_hours' => 72,
        'burst_min_new_accounts' => 5,
        'burst_share' => 0.5,
        'burst_new_account_weight' => 0.3,
    ],
];
