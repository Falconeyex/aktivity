<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

$user = require_auth();

if (request_method() === 'GET') {
    json_ok(['settings' => Auth::settings((int) $user['id']), 'csrf' => Csrf::token()]);
}

require_method('POST');
require_csrf();

$body = request_json();
$language = (string) ($body['language'] ?? '');
$theme = (string) ($body['theme'] ?? '');
$pinned = $body['ai_sidebar_pinned'] ?? null;
$hiddenIn = $body['hidden_columns'] ?? null;

if ($language !== '' && !in_array($language, ['cs', 'en'], true)) {
    json_error('Invalid language');
}
if ($theme !== '' && !in_array($theme, ['light', 'dark'], true)) {
    json_error('Invalid theme');
}

$current = Auth::settings((int) $user['id']);
$language = $language !== '' ? $language : $current['language'];
$theme = $theme !== '' ? $theme : $current['theme'];
$pinValue = $pinned === null ? (int) $current['ai_sidebar_pinned'] : ((int) $pinned ? 1 : 0);
$hidden = $hiddenIn === null
    ? $current['hidden_columns']
    : Auth::normalizeHiddenColumns($hiddenIn);

Auth::ensureSettings((int) $user['id']);
try {
    $stmt = Database::pdo()->prepare(
        'UPDATE user_settings SET language = ?, theme = ?, ai_sidebar_pinned = ?, hidden_columns = ? WHERE user_id = ?'
    );
    $stmt->execute([$language, $theme, $pinValue, json_encode($hidden), (int) $user['id']]);
} catch (PDOException $e) {
    $stmt = Database::pdo()->prepare(
        'UPDATE user_settings SET language = ?, theme = ?, ai_sidebar_pinned = ? WHERE user_id = ?'
    );
    $stmt->execute([$language, $theme, $pinValue, (int) $user['id']]);
}

json_ok(['settings' => Auth::settings((int) $user['id'])]);
