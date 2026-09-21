<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();

$body = request_json();
$email = strtolower(trim((string) ($body['email'] ?? '')));
$password = (string) ($body['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Enter a valid email address');
}
if (!password_is_strong($password)) {
    json_error('Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character');
}

$pdo = Database::pdo();
$exists = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$exists->execute([$email]);
if ($exists->fetch()) {
    json_error('An account with this email already exists', 409);
}

$hash = password_hash($password, PASSWORD_ARGON2ID);
$pdo->beginTransaction();
try {
    $ins = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
    $ins->execute([$email, $hash]);
    $userId = (int) $pdo->lastInsertId();
    Auth::ensureSettings($userId);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error('Registration failed', 500);
}

Auth::login($userId);
json_ok([
    'user' => ['id' => $userId, 'email' => $email],
    'settings' => Auth::settings($userId),
    'csrf' => Csrf::token(),
]);
