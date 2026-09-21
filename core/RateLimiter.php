<?php

declare(strict_types=1);

final class RateLimiter
{
    public static function tooMany(string $email, string $action, int $max, int $windowSeconds): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM auth_attempts
             WHERE email = ? AND ip = ? AND action = ?
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)'
        );
        $stmt->execute([$email, client_ip(), $action, $windowSeconds]);
        return (int) $stmt->fetchColumn() >= $max;
    }

    public static function hit(string $email, string $action): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO auth_attempts (email, ip, action) VALUES (?, ?, ?)'
        );
        $stmt->execute([$email, client_ip(), $action]);
    }

    public static function clear(string $email, string $action): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM auth_attempts WHERE email = ? AND ip = ? AND action = ?'
        );
        $stmt->execute([$email, client_ip(), $action]);
    }
}
