<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

$user = Auth::user();
json_ok([
    'authenticated' => $user !== null,
    'user' => $user ? ['id' => (int) $user['id'], 'email' => $user['email']] : null,
    'settings' => $user ? Auth::settings((int) $user['id']) : Auth::settings(0),
    'csrf' => Csrf::token(),
]);
