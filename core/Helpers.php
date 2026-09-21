<?php

declare(strict_types=1);

/**
 * @param mixed $default
 * @return mixed
 */
function config(string $key, $default = null)
{
    global $CONFIG;
    if (!is_array($CONFIG)) {
        return $default;
    }
    if (array_key_exists($key, $CONFIG)) {
        return $CONFIG[$key];
    }
    return $default;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return is_string($ip) ? substr($ip, 0, 45) : '0.0.0.0';
}

function request_json(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    $cached = is_array($data) ? $data : [];
    return $cached;
}

function request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function require_method(string $method): void
{
    if (request_method() !== strtoupper($method)) {
        json_error('Method not allowed', 405);
    }
}

function require_csrf(): void
{
    Csrf::validate();
}

function require_auth(): array
{
    $user = Auth::user();
    if ($user === null) {
        json_error('Unauthorized', 401);
    }
    return $user;
}

function json_ok(array $data = [], int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $code = 400, array $extra = []): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['ok' => false, 'message' => $message] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

function password_is_strong(string $password): bool
{
    if (strlen($password) < 8) {
        return false;
    }
    return (bool) preg_match('/[A-Z]/', $password)
        && (bool) preg_match('/[a-z]/', $password)
        && (bool) preg_match('/[0-9]/', $password)
        && (bool) preg_match('/[^A-Za-z0-9]/', $password);
}

function app_base(): string
{
    $base = rtrim((string) config('app_base', ''), '/');
    return $base;
}

function asset_url(string $rel): string
{
    $rel = ltrim($rel, '/');
    $full = dirname(__DIR__) . '/assets/' . $rel;
    $v = is_file($full) ? (string) filemtime($full) : '1';
    return app_base() . '/assets/' . $rel . '?v=' . $v;
}

function app_url(): string
{
    return rtrim((string) config('app_url', ''), '/');
}

function card_columns(): array
{
    return ['backlog', 'todo', 'in_progress', 'review', 'done', 'postponed'];
}

function is_valid_column(string $column): bool
{
    return in_array($column, card_columns(), true);
}
