<?php

declare(strict_types=1);

final class History
{
    public static function snapshot(?array $card): ?array
    {
        if ($card === null) {
            return null;
        }
        return [
            'id' => isset($card['id']) ? (int) $card['id'] : null,
            'title' => $card['title'] ?? '',
            'body' => $card['body'] ?? '',
            'status_column' => $card['status_column'] ?? '',
            'order_index' => isset($card['order_index']) ? (int) $card['order_index'] : 0,
            'created_at' => $card['created_at'] ?? null,
            'deleted_at' => $card['deleted_at'] ?? null,
        ];
    }

    public static function log(int $cardId, string $action, ?array $previous, ?array $next): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO card_history (card_id, action_type, previous_state, new_state)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $cardId,
            $action,
            $previous === null ? null : json_encode(self::snapshot($previous), JSON_UNESCAPED_UNICODE),
            $next === null ? null : json_encode(self::snapshot($next), JSON_UNESCAPED_UNICODE),
        ]);
    }
}
