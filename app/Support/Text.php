<?php

namespace App\Support;

/**
 * Mengubah teks biasa dari backoffice menjadi HTML yang rapi:
 * - baris kosong memisahkan paragraf,
 * - pindah baris (Enter) tetap menjadi pindah baris,
 * - baris yang diawali •, -, * menjadi daftar poin, dan 1. 2. 3. menjadi daftar bernomor,
 * - pada daftar poin, teks sebelum titik dua ("Lokasi: ...") ditebalkan.
 * Semua isi di-escape, jadi aman dari HTML yang diketik pengguna.
 */
class Text
{
    public static function html(?string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $text));
        if ($text === '') {
            return '';
        }
        $out = [];
        foreach (preg_split('/\n\s*\n/', $text) as $chunk) {
            $lines = array_values(array_filter(array_map('rtrim', explode("\n", $chunk)), fn ($l) => trim($l) !== ''));
            $buf = [];
            $list = null;
            $items = [];
            $flushP = function () use (&$buf, &$out) {
                if ($buf) {
                    $out[] = '<p>'.implode('<br>', $buf).'</p>';
                    $buf = [];
                }
            };
            $flushL = function () use (&$list, &$items, &$out) {
                if ($list) {
                    $out[] = "<{$list}>".implode('', array_map(fn ($i) => "<li>{$i}</li>", $items))."</{$list}>";
                    $list = null;
                    $items = [];
                }
            };
            foreach ($lines as $line) {
                $t = trim($line);
                if (preg_match('/^(?:[•\-\*·▪◦●]|\x{2013})\s*(.+)$/u', $t, $m)) {
                    $type = 'ul';
                } elseif (preg_match('/^\d{1,2}[.)]\s+(.+)$/u', $t, $m)) {
                    $type = 'ol';
                } else {
                    $flushL();
                    $buf[] = e($t);

                    continue;
                }
                $flushP();
                if ($list !== $type) {
                    $flushL();
                    $list = $type;
                }
                $item = e($m[1]);
                if (preg_match('/^([^:]{2,45}):\s+(.+)$/u', $m[1], $lv)) {
                    $item = '<b>'.e($lv[1]).':</b> '.e($lv[2]);
                }
                $items[] = $item;
            }
            $flushP();
            $flushL();
        }

        return implode("\n", $out);
    }
}
