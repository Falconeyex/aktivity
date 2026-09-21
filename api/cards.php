<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
$user = require_auth();

$deleted = isset($_GET['deleted']) && (string) $_GET['deleted'] === '1';
json_ok(['cards' => Cards::list((int) $user['id'], $deleted)]);
