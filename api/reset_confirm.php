<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();

$body = request_json();
$token = trim((string) ($body['token'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($token === '' || !ctype_xdigit($token) || strlen($token) !== 64) {
    json_error('Invalid or expired reset link');
}
if (!password_is_strong($password)) {
    json_error('Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character');
}

$hash = hash('sha256', $token);
$stmt = Database::pdo()->prepare(
    'SELECT id, user_id, expires_at, used_at
     FROM password_resets
     WHERE token_hash = ?
     ORDER BY id DESC
     LIMIT 1'
);
$stmt->execute([$hash]);
$row = $stmt->fetch();

if (!$row || $row['used_at'] !== null) {
    json_error('Invalid or expired reset link');
}
if (strtotime((string) $row['expires_at']) < time()) {
    json_error('This reset link has expired');
}

$pdo = Database::pdo();
$pdo->beginTransaction();
try {
    $newHash = password_hash($password, PASSWORD_ARGON2ID);
    $updUser = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $updUser->execute([$newHash, (int) $row['user_id']]);
    $updTok = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?');
    $updTok->execute([(int) $row['id']]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error('Password reset failed', 500);
}

json_ok(['message' => 'Password updated. You can sign in now.']);
