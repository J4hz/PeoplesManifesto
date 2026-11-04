<?php

/*
| Hosts such as Laravel Cloud build the app before any mail settings exist.
| Config that throws on a missing value breaks the build, so these must
| resolve to something usable with nothing set.
*/

it('gives the backup package a valid notification address when mail is not set up', function () {
    $keys = ['BACKUP_NOTIFY_EMAIL', 'MAIL_FROM_ADDRESS'];
    $saved = [];
    foreach ($keys as $key) {
        $saved[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }

    try {
        $config = require config_path('backup.php');
    } finally {
        foreach ($saved as $key => [$env, $server, $put]) {
            if ($env !== null) {
                $_ENV[$key] = $env;
            }
            if ($server !== null) {
                $_SERVER[$key] = $server;
            }
            if ($put !== false) {
                putenv("{$key}={$put}");
            }
        }
    }

    expect(filter_var($config['notifications']['mail']['to'], FILTER_VALIDATE_EMAIL))->not->toBeFalse()
        ->and(filter_var($config['notifications']['mail']['from']['address'], FILTER_VALIDATE_EMAIL))->not->toBeFalse();
});
