<?php

declare(strict_types=1);

final class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'blockquote', 'code', 'pre',
        'a', 'span',
    ];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        if (class_exists(\HTMLPurifier::class)) {
            $config = \HTMLPurifier_Config::createDefault();
            $cache = dirname(__DIR__) . '/cache/htmlpurifier';
            if (!is_dir($cache)) {
                @mkdir($cache, 0750, true);
            }
            if (is_dir($cache) && is_writable($cache)) {
                $config->set('Cache.SerializerPath', $cache);
            } else {
                $config->set('Cache.DefinitionImpl', null);
            }
            $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,ul,ol,li,h1,h2,h3,blockquote,code,pre,a[href],span');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $purifier = new \HTMLPurifier($config);
            return $purifier->purify($html);
        }

        return self::allowlist($html);
    }

    private static function allowlist(string $html): string
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div id="aktivity-root">' . $html . '</div>';
        $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('aktivity-root');
        if (!$root) {
            return '';
        }
        self::scrubNode($root);
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        return $out;
    }

    private static function scrubNode(DOMNode $node): void
    {
        $i = 0;
        while ($i < $node->childNodes->length) {
            $child = $node->childNodes->item($i);
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                self::scrubAttributes($child);
                self::scrubNode($child);
            }
            $i++;
        }
    }

    private static function scrubAttributes(DOMElement $el): void
    {
        $keep = [];
        if (strtolower($el->tagName) === 'a' && $el->hasAttribute('href')) {
            $href = trim($el->getAttribute('href'));
            if (preg_match('#^(https?:|mailto:)#i', $href)) {
                $keep['href'] = $href;
            }
        }
        $toRemove = [];
        foreach (iterator_to_array($el->attributes ?? []) as $attr) {
            $toRemove[] = $attr->name;
        }
        foreach ($toRemove as $name) {
            $el->removeAttribute($name);
        }
        foreach ($keep as $name => $value) {
            $el->setAttribute($name, $value);
        }
        if (strtolower($el->tagName) === 'a') {
            $el->setAttribute('rel', 'noopener noreferrer');
            $el->setAttribute('target', '_blank');
        }
    }
}
