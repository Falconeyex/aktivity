<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();
$user = require_auth();

$id = (int) (request_json()['id'] ?? 0);
$card = $id > 0 ? Cards::getOwned((int) $user['id'], $id) : null;
if ($card === null || $card['deleted_at'] !== null) {
    json_error('Card not found', 404);
}

Cards::softDelete($card);
json_ok();
