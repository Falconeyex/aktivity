<?php

declare(strict_types=1);

final class Cards
{
    private const COLS = 'id, user_id, title, body, status_column, order_index, created_at, deleted_at';

    public static function list(int $userId, bool $deleted): array
    {
        $tail = $deleted
            ? 'WHERE user_id = ? AND deleted_at IS NOT NULL ORDER BY deleted_at DESC, id DESC'
            : 'WHERE user_id = ? AND deleted_at IS NULL ORDER BY status_column ASC, order_index ASC, id ASC';
        return self::rows($tail, [$userId]);
    }

    public static function getOwned(int $userId, int $cardId): ?array
    {
        $rows = self::rows('WHERE id = ? AND user_id = ? LIMIT 1', [$cardId, $userId]);
        return $rows[0] ?? null;
    }

    public static function nextIndex(int $userId, string $column): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(MAX(order_index), -1) + 1
             FROM cards WHERE user_id = ? AND status_column = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$userId, $column]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(int $userId, string $title, string $body, string $column): array
    {
        $pdo = Database::pdo();
        $index = self::nextIndex($userId, $column);
        $stmt = $pdo->prepare(
            'INSERT INTO cards (user_id, title, body, status_column, order_index)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $body, $column, $index]);
        $card = self::getOwned($userId, (int) $pdo->lastInsertId());
        History::log((int) $card['id'], 'created', null, $card);
        return $card;
    }

    public static function update(array $card, string $title, string $body): array
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE cards SET title = ?, body = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$title, $body, (int) $card['id'], (int) $card['user_id']]);
        $next = self::getOwned((int) $card['user_id'], (int) $card['id']);
        History::log((int) $card['id'], 'updated', $card, $next);
        return $next;
    }

    public static function setReminder(array $card, ?string $remindAt): array
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE cards SET remind_at = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$remindAt, (int) $card['id'], (int) $card['user_id']]);
        $next = self::getOwned((int) $card['user_id'], (int) $card['id']);
        History::log((int) $card['id'], 'updated', $card, $next);
        return $next;
    }

    public static function move(int $userId, array $items): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                $id = (int) ($item['id'] ?? 0);
                $column = (string) ($item['status_column'] ?? '');
                $index = (int) ($item['order_index'] ?? 0);
                if ($id < 1 || !is_valid_column($column)) {
                    continue;
                }
                $current = self::getOwned($userId, $id);
                if ($current === null || $current['deleted_at'] !== null) {
                    continue;
                }
                $stmt = $pdo->prepare(
                    'UPDATE cards SET status_column = ?, order_index = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL'
                );
                $stmt->execute([$column, $index, $id, $userId]);
                if ($current['status_column'] !== $column || (int) $current['order_index'] !== $index) {
                    $next = $current;
                    $next['status_column'] = $column;
                    $next['order_index'] = $index;
                    if ($current['status_column'] !== $column) {
                        History::log($id, 'moved', $current, $next);
                    }
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function softDelete(array $card): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE cards SET deleted_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([(int) $card['id'], (int) $card['user_id']]);
        $next = self::getOwned((int) $card['user_id'], (int) $card['id']);
        History::log((int) $card['id'], 'deleted', $card, $next);
    }

    public static function restore(array $card): array
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE cards SET deleted_at = NULL WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL'
        );
        $stmt->execute([(int) $card['id'], (int) $card['user_id']]);
        $next = self::getOwned((int) $card['user_id'], (int) $card['id']);
        History::log((int) $card['id'], 'restored', $card, $next);
        return $next;
    }

    public static function purge(array $card): void
    {
        History::log((int) $card['id'], 'purged', $card, null);
        $stmt = Database::pdo()->prepare('DELETE FROM cards WHERE id = ? AND user_id = ?');
        $stmt->execute([(int) $card['id'], (int) $card['user_id']]);
    }

    public static function history(int $userId, int $cardId): array
    {
        $card = self::getOwned($userId, $cardId);
        if ($card === null) {
            return [];
        }
        $stmt = Database::pdo()->prepare(
            'SELECT id, action_type, previous_state, new_state, timestamp
             FROM card_history WHERE card_id = ? ORDER BY timestamp ASC, id ASC'
        );
        $stmt->execute([$cardId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['previous_state'] = $row['previous_state'] !== null ? json_decode((string) $row['previous_state'], true) : null;
            $row['new_state'] = $row['new_state'] !== null ? json_decode((string) $row['new_state'], true) : null;
        }
        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private static function rows(string $sqlTail, array $params): array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT ' . self::COLS . ', remind_at FROM cards ' . $sqlTail);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $stmt = Database::pdo()->prepare('SELECT ' . self::COLS . ' FROM cards ' . $sqlTail);
            $stmt->execute($params);
            $rows = $stmt->fetchAll() ?: [];
            foreach ($rows as &$row) {
                $row['remind_at'] = null;
            }
            return $rows;
        }
    }
}
