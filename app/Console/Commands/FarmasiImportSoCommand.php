<?php

namespace App\Console\Commands;

use App\Models\Medicine;
use App\Models\StockEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Memasukkan hasil stock opname dari SIMRS (database/data/so-simrs-*.json) ke daftar obat dan stok.
 * Obat dicocokkan lewat kode SIMRS, jadi aman dijalankan ulang. Obat berstok 0 di kedua unit dibuat nonaktif.
 * Golongan dan harga diambil dari daftar Excel lama (database/data/obat.json) bila namanya sama persis.
 */
class FarmasiImportSoCommand extends Command
{
    protected $signature = 'farmasi:import-so
        {file=database/data/so-simrs-2026-10-30.json : File JSON hasil stock opname SIMRS}
        {--ganti-daftar-lama : Hapus obat tanpa kode SIMRS yang belum punya isian stok, nonaktifkan yang sudah punya}';

    protected $description = 'Memasukkan hasil stock opname SIMRS ke daftar obat dan stok Farmasi';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (! $data || empty($data['items']) || empty($data['tanggal_so'])) {
            $this->error('File tidak ditemukan atau isinya tidak sesuai: '.$path);

            return self::FAILURE;
        }
        $date = Carbon::createFromFormat('Y-m-d', $data['tanggal_so'])->startOfDay();
        $note = 'SO SIMRS '.$date->format('d/m/Y');
        $excel = collect(json_decode((string) @file_get_contents(database_path('data/obat.json')), true) ?: [])
            ->keyBy(fn ($m) => self::key($m['name']));
        $count = ['baru' => 0, 'diperbarui' => 0, 'aktif' => 0, 'nonaktif' => 0, 'stok' => 0, 'dari_excel' => 0, 'lama_dihapus' => 0, 'lama_dinonaktifkan' => 0];

        DB::transaction(function () use ($data, $date, $note, $excel, &$count) {
            if ($this->option('ganti-daftar-lama')) {
                foreach (Medicine::whereNull('code')->withCount('entries')->get() as $old) {
                    if ($old->entries_count) {
                        $old->update(['is_active' => false]);
                        $count['lama_dinonaktifkan']++;
                    } else {
                        $old->delete();
                        $count['lama_dihapus']++;
                    }
                }
            }

            foreach ($data['items'] as $item) {
                $stock = ['sentral' => (int) $item['sentral'], 'gudang' => (int) $item['gudang']];
                $med = Medicine::firstOrNew(['code' => $item['code']]);
                $old = $excel[self::key($item['name'])] ?? null;
                $unit = Medicine::normalizeUnit($item['unit']);
                $med->fill([
                    'name' => $item['name'],
                    'unit' => $unit,
                    'group' => $med->group ?: ($old['group'] ?? self::guessGroup($item['name'], $unit)),
                    'price' => $med->price ?? ($old['price'] ?? null),
                    'expired_at' => $item['expired_at'] ?? $med->expired_at,
                    'is_active' => array_sum($stock) > 0 || ($med->exists && $med->entries()->exists()),
                ]);
                $count[$med->exists ? 'diperbarui' : 'baru']++;
                $count[$med->is_active ? 'aktif' : 'nonaktif']++;
                $count['dari_excel'] += $old ? 1 : 0;
                $med->save();

                foreach ($stock as $loc => $qty) {
                    if ($qty <= 0) {
                        continue;
                    }
                    $entry = StockEntry::where(['medicine_id' => $med->id, 'location' => $loc])->whereDate('date', $date)->first();
                    $entry
                        ? $entry->update(['counted' => $qty, 'note' => $entry->note ?: $note])
                        : StockEntry::create(['medicine_id' => $med->id, 'location' => $loc, 'date' => $date, 'counted' => $qty, 'note' => $note]);
                    $count['stok']++;
                }
            }
        });

        $this->info('Stock opname SIMRS tanggal '.$date->translatedFormat('d F Y').' selesai dimasukkan.');
        $this->table(['Keterangan', 'Jumlah'], [
            ['Obat baru', $count['baru']], ['Obat diperbarui (kode sudah ada)', $count['diperbarui']],
            ['Aktif (stok > 0)', $count['aktif']], ['Nonaktif (stok 0)', $count['nonaktif']],
            ['Golongan & harga dari Excel lama', $count['dari_excel']], ['Isian stock opname', $count['stok']],
            ['Obat lama dihapus', $count['lama_dihapus']], ['Obat lama dinonaktifkan (punya riwayat)', $count['lama_dinonaktifkan']],
        ]);

        return self::SUCCESS;
    }

    protected static function key(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtoupper($name)), " -*\t");
    }

    /** Perkiraan golongan dari nama dan satuan, untuk obat yang tidak ada di daftar Excel lama. Bisa dirapikan di menu Data obat. */
    public static function guessGroup(string $name, string $unit): string
    {
        $n = ' '.mb_strtoupper($name).' ';
        $has = fn (string $words) => (bool) preg_match('/[^A-Z]('.$words.')[^A-Z]/', $n);

        return match (true) {
            $has('INJ|INJEKSI|INJECTION') => 'INJEKSI',
            $has('INFUS|RINGER|NACL|DEXTROSE|ASERING|KAEN|WIDA|OTSU|WATER FOR') => 'CAIRAN',
            $has('SYR|SIRUP|SYRUP|SUSP|SUSPENSI|ELIXIR|DRY SYRUP') => 'SIRUP',
            $has('DROP|DROPS|TETES') || $unit === 'Tetes' => 'DROPS',
            $has('SALEP|ZALF|CREAM|KRIM|GEL|OINT|LOTION') || $unit === 'Tube' => 'SALEP',
            $has('INHAL|INHALER|NEBU|RESPULES|TURBUHALER|EVOHALER') => 'INHALER',
            $has('SUPP|SUPPO|SUPOSITORIA') || $unit === 'Suppositoria' => 'SUPOSITORIA',
            $has('SUSU|MILK') => 'SUSU',
            in_array($unit, ['Tablet', 'Kapsul'], true) || $has('TAB|TABLET|KAPLET|CAPS|KAPSUL') => 'TABLET',
            in_array($unit, ['Ampul', 'Vial'], true) => 'INJEKSI',
            in_array($unit, ['Pcs', 'Set', 'Gulung', 'Box', 'Pack', 'Syringe', 'Bungkus'], true) => 'ALKES',
            default => 'LAINNYA',
        };
    }
}
