<?php

declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Missing config.php. Copy config.example.php to config.php and set credentials.');
}

$CONFIG = require $configPath;

$vendor = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendor)) {
    require $vendor;
}

require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/History.php';
require_once __DIR__ . '/Cards.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/HtmlSanitizer.php';
require_once __DIR__ . '/OpenAI.php';

set_exception_handler(static function (Throwable $e): void {
    error_log('AKTIVITY: ' . $e->getMessage());
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    if (str_contains($script, '/api/')) {
        if (!headers_sent()) {
            json_error('Server error', 500);
        }
        exit;
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit('Application error.');
});

Session::start();
Csrf::token();
