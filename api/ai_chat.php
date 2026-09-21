<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';
require __DIR__ . '/ai_lib.php';
require_method('POST');
require_csrf();
$user = require_auth();

$text = trim((string) (request_json()['message'] ?? ''));
if ($text === '' || mb_strlen($text) > 4000) {
    json_error('Message is required and must be at most 4000 characters');
}

$messages = ai_messages();
$messages[] = ['role' => 'user', 'content' => $text];
$reply = OpenAI::chat($messages);
$messages[] = ['role' => 'assistant', 'content' => $reply];
ai_set_messages($messages);

json_ok(['reply' => $reply] + ai_payload());
