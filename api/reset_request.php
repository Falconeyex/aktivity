<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();

$body = request_json();
$email = strtolower(trim((string) ($body['email'] ?? '')));
$limits = config('rate_limit');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Enter a valid email address');
}

if (RateLimiter::tooMany($email, 'reset', (int) $limits['reset_max'], (int) $limits['reset_window_seconds'])) {
    json_error('Too many reset requests. Try again later.', 429);
}

RateLimiter::hit($email, 'reset');

$stmt = Database::pdo()->prepare('SELECT id, email FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $ins = Database::pdo()->prepare(
        'INSERT INTO password_resets (user_id, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
    );
    $ins->execute([(int) $user['id'], $hash]);

    $link = app_url() . '/reset_pw.php?token=' . urlencode($token);
    $html = '<p>You requested a password reset for AKTIVITY.</p>'
        . '<p>This link expires in 10 minutes:</p>'
        . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>';
    $sent = Mailer::send($user['email'], 'AKTIVITY password reset', $html, "Reset link (10 minutes):\n$link");
    if (!$sent) {
        json_error('The reset email could not be sent. Check SMTP settings in config.php and try again.', 502);
    }
}

json_ok(['message' => 'If the account exists, a reset email has been sent.']);
