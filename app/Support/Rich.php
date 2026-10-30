<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Teks berformat dari editor backoffice (tebal, miring, daftar poin, daftar bernomor, tautan, subjudul).
 * - sanitize(): hanya menyisakan tag yang aman, semua atribut dibuang kecuali href tautan.
 * - render():   untuk website. Teks lama tanpa HTML tetap tampil rapi lewat Text::html().
 * - plain():    versi teks biasa untuk deskripsi Google, chatbot, dan ringkasan di daftar.
 */
class Rich
{
    protected const KEEP = ['p', 'br', 'strong', 'em', 'u', 'ul', 'ol', 'li', 'a', 'h3', 'h4', 'blockquote'];
    protected const RENAME = ['b' => 'strong', 'i' => 'em', 'div' => 'p', 'h1' => 'h3', 'h2' => 'h3', 'h5' => 'h4', 'h6' => 'h4'];
    protected const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'head', 'title', 'meta', 'link', 'svg', 'img', 'video', 'audio'];

    public static function isHtml(?string $s): bool
    {
        return (bool) preg_match('/<(p|br|ul|ol|li|strong|b|em|i|u|a|h[1-6]|div|blockquote)\b/i', (string) $s);
    }

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        if (! self::isHtml($html)) {
            return Text::html($html);
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="rte-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $doc->getElementById('rte-root');
        if (! $root) {
            return Text::html(strip_tags($html));
        }
        self::clean($root, $doc);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        // Rapikan: buang paragraf kosong dan <br> di ujung
        $out = preg_replace(['#<p>(\s|&nbsp;|<br\s*/?>)*</p>#i', '#(<br\s*/?>\s*)+</(p|li)>#i'], ['', '</$2>'], $out);

        return trim($out);
    }

    protected static function clean(DOMNode $node, DOMDocument $doc): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);

                continue;
            }
            if (isset(self::RENAME[$tag])) {
                $new = $doc->createElement(self::RENAME[$tag]);
                while ($child->firstChild) {
                    $new->appendChild($child->firstChild);
                }
                $node->replaceChild($new, $child);
                $child = $new;
                $tag = $new->tagName;
            }
            self::clean($child, $doc);
            if (! in_array($tag, self::KEEP, true)) {
                // Tag lain (span, font, table, dan sebagainya) dibuka: isinya tetap, tag-nya hilang
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }
            $href = $tag === 'a' ? trim($child->getAttribute('href')) : null;
            foreach (iterator_to_array($child->attributes) as $attr) {
                $child->removeAttribute($attr->name);
            }
            if ($tag === 'a') {
                if ($href && preg_match('#^(https?://|mailto:|tel:|/)#i', $href)) {
                    $child->setAttribute('href', $href);
                    if (preg_match('#^https?://#i', $href)) {
                        $child->setAttribute('target', '_blank');
                        $child->setAttribute('rel', 'noopener');
                    }
                } else {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                }
            }
        }
    }

    /** HTML untuk website. */
    public static function render(?string $s): string
    {
        $s = trim((string) $s);
        if ($s === '') {
            return '';
        }

        return self::isHtml($s) ? self::sanitize($s) : Text::html($s);
    }

    /** Isi awal editor: teks lama diubah ke HTML supaya poin dan paragrafnya langsung terlihat. */
    public static function toEditor(?string $s): string
    {
        return self::render($s);
    }

    /** Teks biasa, misalnya untuk deskripsi di Google atau jawaban chatbot. */
    public static function plain(?string $s, ?int $limit = null): string
    {
        $s = (string) $s;
        if (self::isHtml($s)) {
            $s = preg_replace(['#<li[^>]*>#i', '#</(p|li|h3|h4|blockquote)>#i', '#<br\s*/?>#i'], ['• ', "$0\n", "\n"], $s);
            $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $s = trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\s*\n\s*/', "\n", $s)));

        return $limit ? \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $s), $limit) : $s;
    }
}
