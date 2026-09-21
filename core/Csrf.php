<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validate(): void
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $token = is_string($header) ? $header : '';

        if ($token === '') {
            $json = request_json();
            if (isset($json['csrf_token']) && is_string($json['csrf_token'])) {
                $token = $json['csrf_token'];
            } elseif (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
                $token = $_POST['csrf_token'];
            }
        }

        $session = $_SESSION['csrf_token'] ?? '';
        if (!is_string($session) || $session === '' || !hash_equals($session, $token)) {
            json_error('Invalid CSRF token', 403);
        }
    }
}
