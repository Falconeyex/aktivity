<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
$user = require_auth();

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    json_error('Card not found', 404);
}

$card = Cards::getOwned((int) $user['id'], $id);
if ($card === null) {
    json_error('Card not found', 404);
}

json_ok(['history' => Cards::history((int) $user['id'], $id)]);
