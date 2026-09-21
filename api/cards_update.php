<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();
$user = require_auth();

$body = request_json();
$id = (int) ($body['id'] ?? 0);
$title = trim((string) ($body['title'] ?? ''));
$html = HtmlSanitizer::clean((string) ($body['body'] ?? ''));

if ($id < 1) {
    json_error('Card not found', 404);
}
if ($title === '' || mb_strlen($title) > 255) {
    json_error('Title is required and must be at most 255 characters');
}

$card = Cards::getOwned((int) $user['id'], $id);
if ($card === null || $card['deleted_at'] !== null) {
    json_error('Card not found', 404);
}

json_ok(['card' => Cards::update($card, $title, $html)]);
