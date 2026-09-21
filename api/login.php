<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();

$body = request_json();
$email = strtolower(trim((string) ($body['email'] ?? '')));
$password = (string) ($body['password'] ?? '');
$limits = config('rate_limit');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Enter a valid email address');
}

if (RateLimiter::tooMany($email, 'login', (int) $limits['login_max'], (int) $limits['login_window_seconds'])) {
    json_error('Too many login attempts. Try again later.', 429);
}

$stmt = Database::pdo()->prepare('SELECT id, email, password_hash FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    RateLimiter::hit($email, 'login');
    json_error('Invalid email or password', 401);
}

if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
    $newHash = password_hash($password, PASSWORD_ARGON2ID);
    $upd = Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $upd->execute([$newHash, (int) $user['id']]);
}

RateLimiter::clear($email, 'login');
Auth::login((int) $user['id']);
Auth::ensureSettings((int) $user['id']);

json_ok([
    'user' => ['id' => (int) $user['id'], 'email' => $user['email']],
    'settings' => Auth::settings((int) $user['id']),
    'csrf' => Csrf::token(),
]);
