<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use ZipArchive;

abstract class TestCase extends BaseTestCase
{
    /**
     * Isi sheet pertama file .xlsx hasil unduhan, sebagai baris berisi teks/angka per kolom.
     *
     * @return array<int, array<int, string>>
     */
    protected function xlsxRows(TestResponse $response): array
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $sheet = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();
        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $cells[] = isset($c->is) ? (string) $c->is->t : (string) $c->v;
            }
            $rows[] = $cells;
        }

        return $rows;
    }
}
