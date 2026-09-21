<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();
$user = require_auth();

$body = request_json();
$title = trim((string) ($body['title'] ?? ''));
$html = HtmlSanitizer::clean((string) ($body['body'] ?? ''));
$column = (string) ($body['status_column'] ?? 'backlog');

if ($title === '' || mb_strlen($title) > 255) {
    json_error('Title is required and must be at most 255 characters');
}
if (!is_valid_column($column)) {
    json_error('Invalid column');
}

$card = Cards::create((int) $user['id'], $title, $html, $column);
json_ok(['card' => $card], 201);
