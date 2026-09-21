<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();
$user = require_auth();

$body = request_json();
$id = (int) ($body['id'] ?? 0);
$raw = $body['remind_at'] ?? null;

if ($id < 1) {
    json_error('Card not found', 404);
}

$card = Cards::getOwned((int) $user['id'], $id);
if ($card === null || $card['deleted_at'] !== null) {
    json_error('Card not found', 404);
}

$remindAt = null;
if ($raw !== null && $raw !== '') {
    $raw = trim((string) $raw);
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})(:\d{2})?$/', $raw, $m)) {
        json_error('Invalid reminder datetime');
    }
    $remindAt = $m[1] . ' ' . $m[2] . ':00';
}

try {
    $next = Cards::setReminder($card, $remindAt);
} catch (PDOException $e) {
    json_error('Reminder storage is not available');
}

json_ok(['card' => $next]);
