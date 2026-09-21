<?php

declare(strict_types=1);

/**
 * One-time seeder. Visit once after importing sql/schema.sql.
 * Does nothing if any user already exists.
 */

require __DIR__ . '/core/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::pdo();
} catch (Throwable $e) {
    http_response_code(500);
    exit("Database connection failed. Check config.php.\n");
}

$count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($count > 0) {
    exit("Seed skipped: users already exist.\n");
}

$email = 'falconeyex@gmail.com';
$password = 'Ferdicek2026*';
$hash = password_hash($password, PASSWORD_ARGON2ID);

$cards = [
    ['backlog', 'Collect stakeholder requirements', '<p>Interview product, support, and sales. Capture must-have vs nice-to-have.</p>'],
    ['backlog', 'Map existing workflow', '<p>Document how tasks move today and where handoffs stall.</p>'],
    ['backlog', 'Define done criteria', '<p>Agree on what <strong>Done</strong> means for a card before we start building.</p>'],
    ['backlog', 'Prepare demo script', '<p>Outline a 10-minute walkthrough for the first internal demo.</p>'],
    ['todo', 'Design column layout', '<p>Six columns: Backlog, To Do, In Progress, Review, Done, Postponed.</p>'],
    ['todo', 'Write registration validation', '<p>Email format plus strong password rules (upper, lower, number, symbol).</p>'],
    ['todo', 'Draft Czech UI strings', '<p>Translate chrome only. User card content stays as written.</p>'],
    ['todo', 'Plan recycle bin restore', '<p>Restore must return a card to its previous column and order.</p>'],
    ['in_progress', 'Implement secure sessions', '<p>HttpOnly, Secure, SameSite=Strict, 2-hour idle timeout.</p>'],
    ['in_progress', 'Build drag and drop', '<p>Move cards between columns without a page reload.</p>'],
    ['in_progress', 'Sanitize card HTML', '<p>Purify WYSIWYG output before it is stored.</p>'],
    ['review', 'Review CSRF coverage', '<p>Every POST/PUT/DELETE must validate the synchronizer token.</p>'],
    ['review', 'Check mobile board scroller', '<p>One column in view on phones, snap-scroll to the next.</p>'],
    ['review', 'QA password reset expiry', '<p>Reset links must expire after exactly 10 minutes.</p>'],
    ['done', 'Create MariaDB schema', '<p>New schema only. No legacy scripts.</p>'],
    ['done', 'Seed test account', '<p>Test user and twenty dummy cards for first login.</p>'],
    ['done', 'Set up project folders', '<p>api, assets, core, config isolation.</p>'],
    ['postponed', 'Calendar integrations', '<p>Out of scope for the first release.</p>'],
    ['postponed', 'Mobile native apps', '<p>Responsive web first. Native clients later.</p>'],
    ['postponed', 'Public board sharing', '<p>All records stay isolated per account.</p>'],
];

$pdo->beginTransaction();
try {
    $insUser = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
    $insUser->execute([$email, $hash]);
    $userId = (int) $pdo->lastInsertId();

    $insSettings = $pdo->prepare(
        'INSERT INTO user_settings (user_id, language, theme, ai_sidebar_pinned) VALUES (?, \'en\', \'light\', 1)'
    );
    $insSettings->execute([$userId]);

    $insCard = $pdo->prepare(
        'INSERT INTO cards (user_id, title, body, status_column, order_index) VALUES (?, ?, ?, ?, ?)'
    );
    $indexes = [];
    foreach ($cards as [$column, $title, $body]) {
        $indexes[$column] = ($indexes[$column] ?? -1) + 1;
        $insCard->execute([$userId, $title, $body, $column, $indexes[$column]]);
        $cardId = (int) $pdo->lastInsertId();
        History::log($cardId, 'created', null, [
            'id' => $cardId,
            'title' => $title,
            'body' => $body,
            'status_column' => $column,
            'order_index' => $indexes[$column],
            'created_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    exit("Seed failed.\n");
}

exit("Seed complete. Test account: {$email}\n");
