<?php

declare(strict_types=1);

/**
 * Copy this file to config.php and fill in production values.
 * config.php is not web-accessible (.htaccess) and must not be committed.
 */
return [
    'app_name' => 'AKTIVITY',
    'app_base' => '/aktivity',
    'app_url' => 'https://www.microview.cz/aktivity',

    // Set false for local HTTP development so session cookies are sent.
    // Production (HTTPS) must remain true.
    'session_secure' => true,

    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'aktivity',
        'user' => 'db_user',
        'pass' => 'db_password',
        'charset' => 'utf8mb4',
    ],

    'smtp' => [
        'host' => 'smtp.microview.cz',
        'port' => 587,
        'secure' => 'tls', // tls | ssl | none
        'user' => 'postmaster@microview.cz',
        'pass' => 'smtp_password', // real mailbox password; placeholder skips SMTP and uses PHP mail()
        'from_email' => 'postmaster@microview.cz',
        'from_name' => 'AKTIVITY',
    ],

    'openai' => [
        'api_key' => '',
        'model' => 'gpt-4o-mini',
        'endpoint' => 'https://api.openai.com/v1/chat/completions',
    ],

    'rate_limit' => [
        'login_max' => 8,
        'login_window_seconds' => 900,
        'reset_max' => 5,
        'reset_window_seconds' => 900,
    ],
];
