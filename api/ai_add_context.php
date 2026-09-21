<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require __DIR__ . '/ai_lib.php';
require_method('POST');
require_csrf();
$user = require_auth();

$body = request_json();
$ids = $body['card_ids'] ?? null;
$column = isset($body['column']) ? (string) $body['column'] : '';
$all = !empty($body['all']);

$cards = Cards::list((int) $user['id'], false);
$selected = [];

if ($all) {
    $selected = $cards;
} elseif ($column !== '' && is_valid_column($column)) {
    foreach ($cards as $card) {
        if ($card['status_column'] === $column) {
            $selected[] = $card;
        }
    }
} elseif (is_array($ids)) {
    $want = array_fill_keys(array_map('intval', $ids), true);
    foreach ($cards as $card) {
        if (isset($want[(int) $card['id']])) {
            $selected[] = $card;
        }
    }
}

if ($selected === []) {
    json_error('No cards to add');
}

$blocks = [];
foreach ($selected as $card) {
    $history = Cards::history((int) $user['id'], (int) $card['id']);
    $blocks[] = format_card_context($card, $history);
}

$messages = ai_messages();
$messages[] = [
    'role' => 'system',
    'content' => "The user added the following Kanban card(s) to the chat context:\n\n" . implode("\n\n----\n\n", $blocks),
];
ai_set_messages($messages);
ai_attach_cards($selected);

json_ok(['added' => count($selected)] + ai_payload());
