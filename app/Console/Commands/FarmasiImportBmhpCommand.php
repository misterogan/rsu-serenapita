<?php

namespace App\Console\Commands;

use App\Models\Medicine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Memindahkan barang yang tercantum di daftar BMHP (database/data/bmhp-*.json) dari Data obat ke Data BMHP.
 * Hanya barang yang sudah ada di database yang dipindahkan; nama di daftar yang tidak ditemukan dilaporkan saja.
 * Nama dicocokkan persis, atau setelah akhiran pemasok/batch dibuang ("APRON ONE" = "APRON ONE - DANVI").
 */
class FarmasiImportBmhpCommand extends Command
{
    protected $signature = 'farmasi:import-bmhp
        {file=database/data/bmhp-farmasi-2026-10.json : File JSON daftar BMHP}
        {--dry-run : Hanya tampilkan hasil pencocokan, tanpa mengubah database}';

    protected $description = 'Memisahkan BMHP dari daftar obat berdasarkan file persediaan BMHP';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (empty($data['items'])) {
            $this->error('File tidak ditemukan atau isinya tidak sesuai: '.$path);

            return self::FAILURE;
        }
        $byName = $byBase = [];
        foreach (Medicine::whereNotNull('code')->get() as $m) {
            $byName[self::key($m->name)][] = $m;
            $byBase[self::base($m->name)][] = $m;
        }

        $moves = [];
        $missing = [];
        foreach ($data['items'] as $item) {
            $found = $byName[self::key($item['name'])] ?? $byBase[self::base($item['name'])] ?? [];
            if (! $found) {
                $missing[] = $item['name'];
            }
            foreach ($found as $m) {
                $moves[$m->id] = [$m, $item];
            }
        }
        $already = collect($moves)->filter(fn ($x) => $x[0]->kind === 'bmhp')->count();

        if (! $this->option('dry-run')) {
            DB::transaction(function () use ($moves) {
                foreach ($moves as [$m, $item]) {
                    $m->update(['kind' => 'bmhp', 'group' => 'ALKES', 'expired_at' => $m->expired_at ?? $item['expired_at']]);
                }
            });
        }

        $this->info(($this->option('dry-run') ? '[Uji coba, database tidak diubah] ' : '').'Pemisahan BMHP selesai.');
        $this->table(['Keterangan', 'Jumlah'], [
            ['Nama di daftar BMHP', count($data['items'])],
            ['Nama yang ditemukan di database', count($data['items']) - count($missing)],
            ['Barang dipindahkan ke Data BMHP', count($moves) - $already],
            ['Sudah BMHP sebelumnya', $already],
            ['  di antaranya aktif (ada stok)', collect($moves)->filter(fn ($x) => $x[0]->is_active)->count()],
            ['Nama tidak ditemukan (tidak dimasukkan)', count($missing)],
        ]);
        if ($missing && $this->output->isVerbose()) {
            $this->line('Tidak ditemukan: '.implode('; ', $missing));
        }

        return self::SUCCESS;
    }

    protected static function key(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtoupper($name)), " -*\t");
    }

    /** Nama tanpa akhiran pemasok, nomor batch, ED, atau penanda (GD). */
    protected static function base(string $name): string
    {
        $n = preg_replace(['/\{.*?\}/', '/\(GD\)/'], '', self::key($name));
        $n = explode(' - ', $n)[0];
        $n = preg_replace('/\s*-\s*[A-Z0-9\/]{2,}$/', '', $n);

        return trim(preg_replace('/\s+/', ' ', $n), ' -');
    }
}
