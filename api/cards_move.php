<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();
$user = require_auth();

$body = request_json();
$items = $body['items'] ?? null;
if (!is_array($items)) {
    json_error('Invalid move payload');
}

Cards::move((int) $user['id'], $items);
json_ok(['cards' => Cards::list((int) $user['id'], false)]);
