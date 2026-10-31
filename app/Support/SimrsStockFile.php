<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Membaca file ekspor stok dari SIMRS (.xlsx atau .csv) dengan kolom
 * obatalkes_kode, obatalkes_nama, satuankecil, stok_saatini, tanggal kadaluarsa.
 * File .xlsx dibaca langsung tanpa library tambahan.
 */
class SimrsStockFile
{
    /**
     * @return array{sheet: string, sheets: string[], rows: array<int, array{code: string, name: string, unit: string, stock: int}>}
     */
    public static function read(string $path, string $original, ?string $sheet = null): array
    {
        $sheets = str_ends_with(strtolower($original), '.csv') ? ['CSV' => self::csv($path)] : self::xlsx($path);
        $parsed = [];
        foreach ($sheets as $name => $rows) {
            if ($table = self::table($rows)) {
                $parsed[$name] = $table;
            }
        }
        if (! $parsed) {
            throw new RuntimeException('Kolom obatalkes_kode dan stok_saatini tidak ditemukan. Pastikan file adalah ekspor stok dari SIMRS.');
        }
        if ($sheet !== null && $sheet !== '' && ! isset($parsed[$sheet])) {
            throw new RuntimeException('Sheet "'.$sheet.'" tidak ada atau formatnya tidak sesuai. Sheet yang bisa dipakai: '.implode(', ', array_keys($parsed)).'.');
        }
        // Tanpa pilihan: pakai sheet dengan isian stok terbanyak
        $sheet = $sheet ?: collect($parsed)->sortByDesc(fn ($t) => $t['filled'])->keys()->first();

        return ['sheet' => $sheet, 'sheets' => array_keys($parsed), 'rows' => $parsed[$sheet]['rows']];
    }

    /** @return array{filled: int, rows: array<int, array{code: string, name: string, unit: string, stock: int}>}|null */
    protected static function table(array $rows): ?array
    {
        foreach ($rows as $i => $row) {
            $head = array_map(fn ($v) => strtolower(trim((string) $v)), $row);
            $col = fn (string $needle) => collect($head)->search(fn ($h) => str_contains($h, $needle));
            $code = $col('kode');
            $stock = $col('stok');
            if ($code === false || $stock === false) {
                continue;
            }
            $name = $col('nama');
            $unit = $col('satuan');
            $out = [];
            $filled = 0;
            foreach (array_slice($rows, $i + 1) as $r) {
                $n = trim(preg_replace('/\s+/', ' ', (string) ($r[$name] ?? '')));
                $c = trim((string) ($r[$code] ?? ''));
                if ($n === '' && $c === '') {
                    continue;
                }
                $raw = trim((string) ($r[$stock] ?? ''));
                $filled += $raw !== '' ? 1 : 0;
                $out[] = ['code' => $c, 'name' => $n, 'unit' => trim((string) ($r[$unit] ?? '')), 'stock' => (int) round((float) str_replace(',', '.', $raw))];
            }

            return ['filled' => $filled, 'rows' => $out];
        }

        return null;
    }

    /** @return array<int, array<int, string>> */
    protected static function csv(string $path): array
    {
        $text = (string) file_get_contents($path);
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $first = strtok($text, "\n") ?: '';
        $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $rows = [];
        foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
            if ($line !== '') {
                $rows[] = str_getcsv($line, $delim, '"', '');
            }
        }

        return $rows;
    }

    /** @return array<string, array<int, array<int, string>>> nama sheet => baris => kolom */
    protected static function xlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File tidak bisa dibuka. Pastikan formatnya .xlsx atau .csv.');
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
