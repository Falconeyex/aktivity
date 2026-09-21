<?php

declare(strict_types=1);

final class Export
{
    private const FORMATS = ['json', 'html', 'xml', 'csv'];

    public static function isValidFormat(string $format): bool
    {
        return in_array($format, self::FORMATS, true);
    }

    /**
     * @param list<array<string, mixed>> $cards
     * @return array{filename: string, mime: string, body: string}
     */
    public static function build(array $cards, string $format, string $lang): array
    {
        $stamp = date('Y-m-d-His');
        $rows = array_map([self::class, 'publicCard'], $cards);
        return match ($format) {
            'html' => [
                'filename' => 'aktivity-export-' . $stamp . '.html',
                'mime' => 'text/html; charset=utf-8',
                'body' => self::toHtml($rows, $lang),
            ],
            'xml' => [
                'filename' => 'aktivity-export-' . $stamp . '.xml',
                'mime' => 'application/xml; charset=utf-8',
                'body' => self::toXml($rows),
            ],
            'csv' => [
                'filename' => 'aktivity-export-' . $stamp . '.csv',
                'mime' => 'text/csv; charset=utf-8',
                'body' => self::toCsv($rows, $lang),
            ],
            default => [
                'filename' => 'aktivity-export-' . $stamp . '.json',
                'mime' => 'application/json; charset=utf-8',
                'body' => self::toJson($rows),
            ],
        };
    }

    /** @param array{filename: string, mime: string, body: string} $file */
    public static function download(array $file): never
    {
        $name = $file['filename'];
        header('Content-Type: ' . $file['mime']);
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        echo $file['body'];
        exit;
    }

    /**
     * @param array<string, mixed> $card
     * @return array{id: int, title: string, body: string, status_column: string, order_index: int, created_at: string}
     */
    private static function publicCard(array $card): array
    {
        return [
            'id' => (int) ($card['id'] ?? 0),
            'title' => (string) ($card['title'] ?? ''),
            'body' => (string) ($card['body'] ?? ''),
            'status_column' => (string) ($card['status_column'] ?? ''),
            'order_index' => (int) ($card['order_index'] ?? 0),
            'created_at' => (string) ($card['created_at'] ?? ''),
        ];
    }

    /** @param list<array{id: int, title: string, body: string, status_column: string, order_index: int, created_at: string}> $cards */
    private static function toJson(array $cards): string
    {
        return (string) json_encode([
            'exported_at' => date('c'),
            'cards' => $cards,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /** @param list<array{id: int, title: string, body: string, status_column: string, order_index: int, created_at: string}> $cards */
    private static function toXml(array $cards): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<aktivity exported_at="' . self::xml(date('c')) . '">' . "\n";
        foreach ($cards as $card) {
            $out .= '  <card id="' . (int) $card['id'] . '">' . "\n";
            $out .= '    <title>' . self::xml($card['title']) . '</title>' . "\n";
            $out .= '    <column>' . self::xml($card['status_column']) . '</column>' . "\n";
            $out .= '    <order>' . (int) $card['order_index'] . '</order>' . "\n";
            $out .= '    <created_at>' . self::xml($card['created_at']) . '</created_at>' . "\n";
            $out .= '    <body><![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $card['body']) . ']]></body>' . "\n";
            $out .= '  </card>' . "\n";
        }
        $out .= '</aktivity>' . "\n";
        return $out;
    }

    /** @param list<array{id: int, title: string, body: string, status_column: string, order_index: int, created_at: string}> $cards */
    private static function toCsv(array $cards, string $lang): string
    {
        $headers = $lang === 'cs'
            ? ['ID', 'Název', 'Sloupec', 'Pořadí', 'Vytvořeno', 'Text']
            : ['ID', 'Title', 'Column', 'Order', 'Created', 'Body'];
        $lines = [self::csvLine($headers)];
        foreach ($cards as $card) {
            $lines[] = self::csvLine([
                (string) $card['id'],
                $card['title'],
                self::columnLabel($card['status_column'], $lang),
                (string) $card['order_index'],
                $card['created_at'],
                self::plainText($card['body']),
            ]);
        }
        return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
    }

    /** @param list<array{id: int, title: string, body: string, status_column: string, order_index: int, created_at: string}> $cards */
    private static function toHtml(array $cards, string $lang): string
    {
        $byCol = [];
        foreach (card_columns() as $col) {
            $byCol[$col] = [];
        }
        foreach ($cards as $card) {
            $col = $card['status_column'];
            if (!isset($byCol[$col])) {
                $byCol[$col] = [];
            }
            $byCol[$col][] = $card;
        }
        $title = $lang === 'cs' ? 'Export nástěnky AKTIVITY' : 'AKTIVITY board export';
        $createdLabel = $lang === 'cs' ? 'karta vytvořena' : 'card created';
        $html = '<!DOCTYPE html><html lang="' . ($lang === 'cs' ? 'cs' : 'en') . '"><head><meta charset="utf-8">';
        $html .= '<title>' . e($title) . '</title>';
        $html .= '<style>body{font-family:Segoe UI,system-ui,sans-serif;margin:24px;color:#152033}';
        $html .= 'h1{font-size:1.4rem}h2{margin-top:28px;font-size:1.1rem}';
        $html .= 'article{border:1px solid #d4deea;border-radius:12px;padding:12px 16px;margin:12px 0}';
        $html .= 'h3{margin:0 0 6px}time{color:#5b6b80;font-size:.85rem}</style></head><body>';
        $html .= '<h1>' . e($title) . '</h1>';
        $html .= '<p>' . e(date('Y-m-d H:i:s')) . '</p>';
        foreach ($byCol as $col => $items) {
            if ($items === []) {
                continue;
            }
            $html .= '<h2>' . e(self::columnLabel($col, $lang)) . '</h2>';
            foreach ($items as $card) {
                $html .= '<article><h3>' . e($card['title']) . '</h3>';
                $html .= '<time>' . e($createdLabel . ': ' . $card['created_at']) . '</time>';
                $html .= '<div>' . HtmlSanitizer::clean($card['body']) . '</div></article>';
            }
        }
        $html .= '</body></html>';
        return $html;
    }

    /** @param list<string> $fields */
    private static function csvLine(array $fields): string
    {
        $out = [];
        foreach ($fields as $field) {
            $field = str_replace(["\r\n", "\r"], "\n", $field);
            if (str_contains($field, '"') || str_contains($field, ',') || str_contains($field, "\n") || str_contains($field, ';')) {
                $out[] = '"' . str_replace('"', '""', $field) . '"';
            } else {
                $out[] = $field;
            }
        }
        return implode(',', $out);
    }

    private static function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/[ \t]+/u', ' ', $text) ?? $text);
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function columnLabel(string $column, string $lang): string
    {
        $en = [
            'backlog' => 'Backlog',
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'done' => 'Done',
            'postponed' => 'Postponed',
        ];
        $cs = [
            'backlog' => 'Fronta',
            'todo' => 'K udělání',
            'in_progress' => 'Probíhá',
            'review' => 'Kontrola',
            'done' => 'Hotovo',
            'postponed' => 'Odloženo',
        ];
        $map = $lang === 'cs' ? $cs : $en;
        return $map[$column] ?? $column;
    }
}
