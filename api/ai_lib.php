<?php

declare(strict_types=1);

function ai_messages(): array
{
    if (!isset($_SESSION['ai_messages']) || !is_array($_SESSION['ai_messages'])) {
        $_SESSION['ai_messages'] = [
            [
                'role' => 'system',
                'content' => 'You are an assistant helping the user manage a Kanban board. Use the supplied card context, including any reminder date and time and whether it is overdue, when relevant. Reply in the same language the user writes in.',
            ],
        ];
    }
    return $_SESSION['ai_messages'];
}

function ai_set_messages(array $messages): void
{
    $_SESSION['ai_messages'] = $messages;
}

function ai_attached(): array
{
    if (!isset($_SESSION['ai_attached']) || !is_array($_SESSION['ai_attached'])) {
        $_SESSION['ai_attached'] = [];
    }
    return $_SESSION['ai_attached'];
}

function ai_attach_cards(array $cards): array
{
    $attached = ai_attached();
    $known = [];
    foreach ($attached as $item) {
        $known[(int) ($item['id'] ?? 0)] = true;
    }
    foreach ($cards as $card) {
        $id = (int) ($card['id'] ?? 0);
        if ($id < 1 || isset($known[$id])) {
            continue;
        }
        $attached[] = [
            'id' => $id,
            'title' => (string) ($card['title'] ?? ''),
            'remind_at' => $card['remind_at'] ?? null,
        ];
        $known[$id] = true;
    }
    $_SESSION['ai_attached'] = $attached;
    return $attached;
}

function ai_clear_state(): void
{
    unset($_SESSION['ai_messages'], $_SESSION['ai_context'], $_SESSION['ai_attached']);
}

function ai_public_messages(): array
{
    $out = [];
    foreach (ai_messages() as $msg) {
        if (($msg['role'] ?? '') === 'system') {
            continue;
        }
        $out[] = [
            'role' => $msg['role'],
            'content' => $msg['content'],
        ];
    }
    return $out;
}

function ai_payload(): array
{
    return [
        'messages' => ai_public_messages(),
        'attached' => ai_attached(),
    ];
}

function format_card_context(array $card, array $history): string
{
    $lines = [];
    $lines[] = 'Card #' . (int) $card['id'];
    $lines[] = 'Title: ' . (string) $card['title'];
    $lines[] = 'Column: ' . (string) $card['status_column'];
    $lines[] = 'Created: ' . (string) $card['created_at'];
    $remind = trim((string) ($card['remind_at'] ?? ''));
    if ($remind !== '') {
        $ts = strtotime($remind);
        $state = ($ts !== false && $ts < time()) ? 'overdue' : 'upcoming';
        $lines[] = 'Reminder: ' . $remind . ' (' . $state . ')';
    } else {
        $lines[] = 'Reminder: (none)';
    }
    $lines[] = 'Body:';
    $lines[] = trim(html_entity_decode(strip_tags((string) $card['body']))) ?: '(empty)';
    $lines[] = 'History:';
    if ($history === []) {
        $lines[] = '(none)';
    } else {
        foreach ($history as $row) {
            $prevCol = $row['previous_state']['status_column'] ?? '';
            $nextCol = $row['new_state']['status_column'] ?? '';
            $lines[] = sprintf(
                '- %s @ %s%s',
                $row['action_type'],
                $row['timestamp'],
                ($prevCol || $nextCol) ? " ({$prevCol} -> {$nextCol})" : ''
            );
        }
    }
    return implode("\n", $lines);
}
