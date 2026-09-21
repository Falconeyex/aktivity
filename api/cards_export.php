<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require_method('POST');
require_csrf();
$user = require_auth();

$body = request_json();
$format = strtolower(trim((string) ($body['format'] ?? 'json')));
$all = !empty($body['all']);
$idsIn = $body['card_ids'] ?? null;

if (!Export::isValidFormat($format)) {
    json_error('Invalid export format');
}

$cards = Cards::list((int) $user['id'], false);
if (!$all) {
    if (!is_array($idsIn)) {
        json_error('Select at least one card');
    }
    $wanted = [];
    foreach ($idsIn as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $wanted[$id] = true;
        }
    }
    $cards = array_values(array_filter(
        $cards,
        static fn(array $card): bool => isset($wanted[(int) $card['id']])
    ));
    if ($cards === []) {
        json_error('Select at least one card');
    }
}

$settings = Auth::settings((int) $user['id']);
$lang = ($settings['language'] ?? 'en') === 'cs' ? 'cs' : 'en';
$file = Export::build($cards, $format, $lang);
json_ok([
    'filename' => $file['filename'],
    'mime' => $file['mime'],
    'content' => base64_encode($file['body']),
]);
