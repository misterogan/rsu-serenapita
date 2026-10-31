<?php

namespace App\Support;

use App\Models\Medicine;
use Illuminate\Support\Carbon;

/**
 * Mencocokkan stok hasil ekspor SIMRS dengan stok di sistem ini, per kode SIMRS.
 * Stok sistem diambil pada akhir tanggal yang dipilih untuk unit yang dipilih.
 */
class StockCompare
{
    public const STATUS = [
        'selisih' => ['Selisih', 'red'],
        'cocok' => ['Cocok', 'green'],
        'baru' => ['Belum ada di sistem', 'amber'],
        'hilang' => ['Tidak ada di SIMRS', 'amber'],
        'tanpa_kode' => ['Tanpa kode SIMRS', 'grey'],
    ];

    /**
     * @param  array<int, array{code: string, name: string, unit: string, stock: int}>  $simrs
     * @return array<int, object{code: string, simrsName: ?string, med: ?Medicine, simrs: ?int, system: ?int, diff: ?int, status: string, renamed: bool}>
     */
    public static function run(array $simrs, string $location, Carbon $date): array
    {
        $meds = Medicine::whereNotNull('code')->get()->keyBy('code');
        $stock = array_map(fn ($s) => $s['end'], StockLedger::summary($location, $date, $date));
        $byCode = [];
        $out = [];
        foreach ($simrs as $r) {
            if ($r['code'] === '') {
                $out[] = (object) ['code' => '', 'simrsName' => $r['name'], 'med' => null, 'simrs' => $r['stock'], 'system' => null, 'diff' => null, 'status' => 'tanpa_kode', 'renamed' => false];

                continue;
            }
            // Kode yang muncul dua kali di file SIMRS dijumlahkan
            if (isset($byCode[$r['code']])) {
                $byCode[$r['code']]->simrs += $r['stock'];

                continue;
            }
            $byCode[$r['code']] = (object) ['code' => $r['code'], 'simrsName' => $r['name'], 'med' => $meds[$r['code']] ?? null, 'simrs' => $r['stock']];
        }
        foreach ($byCode as $row) {
            $row->system = $row->med ? ($stock[$row->med->id] ?? 0) : null;
            $row->diff = $row->med ? $row->system - $row->simrs : null;
            $row->status = ! $row->med ? 'baru' : ($row->diff === 0 ? 'cocok' : 'selisih');
            $row->renamed = $row->med && self::key($row->med->name) !== self::key($row->simrsName);
            $out[] = $row;
        }
        // Barang yang punya stok di sistem tetapi tidak ada di file SIMRS
        foreach (Medicine::whereIn('id', array_keys(array_filter($stock)))->get() as $m) {
            if (! $m->code || ! isset($byCode[$m->code])) {
                $out[] = (object) ['code' => (string) $m->code, 'simrsName' => null, 'med' => $m, 'simrs' => null, 'system' => $stock[$m->id], 'diff' => $stock[$m->id], 'status' => 'hilang', 'renamed' => false];
            }
        }

        return $out;
    }

    /** Kode baru dari SIMRS yang layak ditambahkan (bukan data percobaan "DUMMY"). */
    public static function addable(object $row): bool
    {
        return $row->status === 'baru' && $row->simrsName !== '' && ! str_contains(mb_strtoupper($row->simrsName), 'DUMMY');
    }

    public static function key(?string $name): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $name)));
    }
}
