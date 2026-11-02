<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Membuat file Excel (.xlsx) satu sheet tanpa library tambahan: baris judul, kepala kolom tebal yang terkunci
 * saat digulir dan bisa disaring, angka berformat ribuan, serta lebar kolom yang pas.
 */
class XlsxWriter
{
    /** Gaya sel di styles.xml */
    public const TEXT = 0;

    public const HEAD = 1;

    public const INT = 2;

    public const DEC = 3;

    public const TITLE = 4;

    public const DATE = 5;

    /**
     * @param  string[]  $titles  baris keterangan di atas tabel
     * @param  array<int, array{0: string, 1: int, 2?: int}>  $columns  [judul kolom, gaya, lebar]
     * @param  iterable<array<int, mixed>>  $rows
     * @return string path file sementara
     */
    public static function build(string $sheet, array $titles, array $columns, iterable $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File Excel tidak bisa dibuat.');
        }
        $headRow = count($titles) + 1;
        $lastCol = self::col(count($columns) - 1);
        $xml = [];
        $r = 0;
        foreach ($titles as $t) {
            $r++;
            $xml[] = '<row r="'.$r.'">'.self::cell('A'.$r, $t, self::TITLE).'</row>';
        }
        $r++;
        $xml[] = '<row r="'.$r.'">'.implode('', array_map(fn ($c, $i) => self::cell(self::col($i).$r, $c[0], self::HEAD), $columns, array_keys($columns))).'</row>';
        foreach ($rows as $row) {
            $r++;
            $cells = '';
            foreach (array_values($row) as $i => $v) {
                $cells .= self::cell(self::col($i).$r, $v, $columns[$i][1] ?? self::TEXT);
            }
            $xml[] = '<row r="'.$r.'">'.$cells.'</row>';
        }
        $cols = implode('', array_map(fn ($c, $i) => '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.($c[2] ?? 12).'" customWidth="1"/>', $columns, array_keys($columns)));

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.self::esc(mb_substr(preg_replace('#[\\\\/?*\[\]:]#', ' ', $sheet), 0, 31)).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0.00"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/></numFmts><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="12"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F6FB2"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="6"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$headRow.'" topLeftCell="A'.($headRow + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>'.$cols.'</cols><sheetData>'.implode('', $xml).'</sheetData><autoFilter ref="A'.$headRow.':'.$lastCol.max($headRow, $r).'"/></worksheet>');
        $zip->close();

        return $path;
    }

    protected static function cell(string $ref, mixed $v, int $style): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        if ($v instanceof \DateTimeInterface) {
            // Tanggal Excel = jumlah hari sejak 30 Desember 1899
            return '<c r="'.$ref.'" s="'.self::DATE.'"><v>'.(intdiv(($d = \DateTimeImmutable::createFromInterface($v)->setTime(0, 0))->getTimestamp() + $d->getOffset(), 86400) + 25569).'</v></c>';
        }
        if ((is_int($v) || is_float($v)) && in_array($style, [self::INT, self::DEC], true)) {
            return '<c r="'.$ref.'" s="'.$style.'"><v>'.(is_float($v) ? round($v, 4) : $v).'</v></c>';
        }

        return '<c r="'.$ref.'" s="'.($style === self::INT || $style === self::DEC ? self::TEXT : $style).'" t="inlineStr"><is><t xml:space="preserve">'.self::esc((string) $v).'</t></is></c>';
    }

    protected static function esc(string $s): string
    {
        return htmlspecialchars(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $s), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Huruf kolom dari indeks 0: 0 = A, 26 = AA. */
    public static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26).$s;
        }

        return $s;
    }
}
