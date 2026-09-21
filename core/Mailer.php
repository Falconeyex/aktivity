<?php

declare(strict_types=1);

final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $smtp = config('smtp');
        if (!is_array($smtp)) {
            $smtp = [];
        }

        $smtpOk = false;
        if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class) && self::smtpReady($smtp)) {
            $smtpOk = self::sendPhpMailer($to, $subject, $htmlBody, $textBody, $smtp);
            if ($smtpOk) {
                return true;
            }
        }

        return self::sendMail($to, $subject, $htmlBody, $smtp);
    }

    private static function smtpReady(array $smtp): bool
    {
        $host = trim((string) ($smtp['host'] ?? ''));
        $pass = trim((string) ($smtp['pass'] ?? ''));
        if ($host === '') {
            return false;
        }
        return $pass !== '' && $pass !== 'smtp_password';
    }

    private static function sendPhpMailer(string $to, string $subject, string $htmlBody, string $textBody, array $smtp): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Timeout = 12;
            $mail->Host = (string) $smtp['host'];
            $mail->Port = (int) ($smtp['port'] ?: 587);
            $mail->SMTPAuth = true;
            $mail->Username = (string) $smtp['user'];
            $mail->Password = (string) $smtp['pass'];
            $secure = strtolower((string) ($smtp['secure'] ?? 'tls'));
            if ($secure === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'none' || $secure === '') {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            $mail->setFrom((string) $smtp['from_email'], (string) ($smtp['from_name'] ?? 'AKTIVITY'));
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody !== '' ? $textBody : trim(strip_tags($htmlBody));
            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('AKTIVITY mail error: ' . $e->getMessage());
            return false;
        }
    }

    private static function sendMail(string $to, string $subject, string $htmlBody, array $smtp): bool
    {
        $from = (string) ($smtp['from_email'] ?? 'postmaster@microview.cz');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from,
        ];
        $encoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        return @mail($to, $encoded, $htmlBody, implode("\r\n", $headers), '-f' . $from);
    }
}
