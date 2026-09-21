<?php

declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return null;
        }
        $id = (int) $id;
        $stmt = Database::pdo()->prepare('SELECT id, email, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function settings(?int $userId = null): array
    {
        $id = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
        $defaults = [
            'language' => 'en',
            'theme' => 'light',
            'ai_sidebar_pinned' => 1,
            'hidden_columns' => [],
        ];
        if ($id < 1) {
            return $defaults;
        }
        try {
            $stmt = Database::pdo()->prepare(
                'SELECT language, theme, ai_sidebar_pinned, hidden_columns FROM user_settings WHERE user_id = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();
        } catch (PDOException $e) {
            $stmt = Database::pdo()->prepare(
                'SELECT language, theme, ai_sidebar_pinned FROM user_settings WHERE user_id = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                $row['hidden_columns'] = '[]';
            }
        }
        if (!$row) {
            return $defaults;
        }
        $row['ai_sidebar_pinned'] = (int) $row['ai_sidebar_pinned'];
        $row['hidden_columns'] = self::normalizeHiddenColumns($row['hidden_columns'] ?? []);
        return $row;
    }

    public static function normalizeHiddenColumns(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $col) {
            $col = (string) $col;
            if (is_valid_column($col) && !in_array($col, $out, true)) {
                $out[] = $col;
            }
        }
        if (count($out) >= count(card_columns())) {
            array_pop($out);
        }
        return $out;
    }

    public static function login(int $userId): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();
        unset($_SESSION['ai_messages'], $_SESSION['ai_context'], $_SESSION['ai_attached']);
        Csrf::token();
    }

    public static function logout(): void
    {
        Session::destroy();
        Session::start();
        Csrf::token();
    }

    public static function ensureSettings(int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT IGNORE INTO user_settings (user_id, language, theme, ai_sidebar_pinned) VALUES (?, \'en\', \'light\', 1)'
        );
        $stmt->execute([$userId]);
    }
}
