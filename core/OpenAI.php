<?php

declare(strict_types=1);

final class OpenAI
{
    public static function chat(array $messages): string
    {
        $cfg = config('openai');
        $key = trim((string) ($cfg['api_key'] ?? ''));
        if ($key === '') {
            json_error('ChatGPT API key is not configured', 503);
        }

        $payload = json_encode([
            'model' => (string) ($cfg['model'] ?? 'gpt-4o-mini'),
            'messages' => $messages,
            'temperature' => 0.4,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init((string) ($cfg['endpoint'] ?? 'https://api.openai.com/v1/chat/completions'));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $key,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $errno !== 0) {
            json_error('ChatGPT request failed', 502);
        }

        $data = json_decode((string) $raw, true);
        if ($status >= 400 || !is_array($data)) {
            $msg = is_array($data) ? (string) ($data['error']['message'] ?? 'ChatGPT API error') : 'ChatGPT API error';
            json_error($msg, 502);
        }

        $text = $data['choices'][0]['message']['content'] ?? '';
        if (!is_string($text) || trim($text) === '') {
            json_error('Empty response from ChatGPT', 502);
        }
        return $text;
    }
}
