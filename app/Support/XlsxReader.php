<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/** Membaca semua sheet file .xlsx tanpa library tambahan (nilai sel sebagai teks). */
class XlsxReader
{
    /** @return array<string, array<int, array<int, string>>> nama sheet => baris => kolom */
    public static function sheets(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File tidak bisa dibuka. Pastikan formatnya .xlsx.');
        }
        $xml = fn (string $name) => ($s = $zip->getFromName($name)) === false ? null : simplexml_load_string($s);
        $strings = [];
        if ($sst = $xml('xl/sharedStrings.xml')) {
            foreach ($sst->si as $si) {
                $strings[] = isset($si->t) ? (string) $si->t : implode('', array_map(fn ($r) => (string) $r->t, iterator_to_array($si->r, false)));
            }
        }
        $targets = [];
        foreach ($xml('xl/_rels/workbook.xml.rels')?->Relationship ?? [] as $rel) {
            $targets[(string) $rel['Id']] = ltrim(preg_replace('#^/?xl/#', '', (string) $rel['Target']), '/');
        }
        $out = [];
        foreach ($xml('xl/workbook.xml')?->sheets->sheet ?? [] as $sh) {
            $rid = (string) $sh->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $data = isset($targets[$rid]) ? $xml('xl/'.$targets[$rid]) : null;
            $rows = [];
            foreach ($data?->sheetData->row ?? [] as $row) {
                $cells = [];
                foreach ($row->c as $c) {
                    $idx = self::colIndex((string) $c['r']);
                    $type = (string) $c['t'];
                    $cells[$idx] = match ($type) {
                        's' => $strings[(int) $c->v] ?? '',
                        'inlineStr' => (string) $c->is->t,
                        default => (string) $c->v,
                    };
                }
                if ($cells) {
                    $max = max(array_keys($cells));
                    $rows[] = array_replace(array_fill(0, $max + 1, ''), $cells);
                }
            }
            $out[(string) $sh['name']] = $rows;
        }
        $zip->close();

        return $out;
    }

    protected static function colIndex(string $ref): int
    {
        $letters = preg_replace('/\d+/', '', $ref);
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + ord($ch) - 64;
        }

        return $n - 1;
    }
}
