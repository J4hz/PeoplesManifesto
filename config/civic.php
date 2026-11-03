<?php

return [

    /*
    | Issue categories. The key is stored in the database, the label comes from
    | lang/{locale}/civic.php. Order here is the order of the chips on the form.
    */
    'categories' => [
        'water',
        'roads',
        'security',
        'health',
        'schools',
        'sanitation',
        'electricity',
        'markets',
        'environment',
        'other',
    ],

    'severities' => [
        'annoying' => 1,
        'serious' => 2,
        'urgent' => 3,
    ],

    /*
    | Which office a category is routed to by default. County functions under
    | the Fourth Schedule (water, county roads, health, waste, markets) go to
    | the MCA, who sits closest to the ward. NG-CDF and national government
    | matters (schools, security, electricity) go to the MP.
    */
    'routing' => [
        'water' => 'mca',
        'roads' => 'mca',
        'health' => 'mca',
        'sanitation' => 'mca',
        'markets' => 'mca',
        'environment' => 'mca',
        'schools' => 'mp',
        'security' => 'mp',
        'electricity' => 'mp',
        'other' => 'mca',
    ],

    'office_types' => ['mca', 'mp', 'governor', 'senator', 'women_rep'],

    'statuses' => ['open', 'acknowledged', 'in_progress', 'done', 'reopened'],

    // Statuses a leader can set from the respond form.
    'leader_statuses' => ['acknowledged', 'in_progress', 'done'],

    'clustering' => [
        // Cosine similarity needed to join an existing cluster.
        'similarity_threshold' => (float) env('CLUSTER_SIMILARITY_THRESHOLD', 0.82),
        // Used with the local hashing embedder, whose scores run much lower.
        'local_similarity_threshold' => 0.3,
        // Done clusters are not matched, a new report after Done starts fresh.
        'match_statuses' => ['open', 'acknowledged', 'in_progress', 'reopened'],
        // Member counts at which the cluster title and summary are rewritten.
        'summary_refresh_at' => [3, 10, 25, 50, 100, 250],
    ],

    'rating' => [
        'window_days' => 7,
        'min_ratings_for_early_decision' => 20,
        // Reopen when the share of "not fixed" is strictly above this.
        'reopen_share' => 0.5,
        'praise_tags' => ['fast_response', 'kept_us_updated', 'good_quality_work', 'listened_to_residents'],
    ],

    'photos' => [
        'max_kilobytes' => 5120,
        'max_dimension' => 1600,
        // "local" on a single server. On hosts where web and workers do not share a disk, a private S3 bucket.
        'disk' => env('PHOTOS_DISK', 'local'),
    ],

    'otp' => [
        'length' => 6,
        'ttl_minutes' => 10,
        'max_attempts' => 5,
    ],

    'sms' => [
        // Unfinished SMS conversations are dropped after this long.
        'session_minutes' => 30,
    ],

    'locales' => ['en', 'sw'],

    /*
    | Who runs the platform. Shown on the privacy notice. Sentences that depend
    | on an empty value are left out rather than filled with placeholders.
    */
    'operator' => [
        // TODO: the registered name of the organisation that runs the platform.
        'name' => env('OPERATOR_NAME'),
        // TODO: the Data Protection Officer's or privacy contact email.
        'privacy_email' => env('OPERATOR_PRIVACY_EMAIL'),
        // TODO: ODPC registration number once registration as a data controller is complete.
        'odpc_registration' => env('OPERATOR_ODPC_REGISTRATION'),
    ],
];
