<?php

namespace App\Services\Khanza;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Data pasien dan riwayat kunjungan, dibaca langsung dari database SIMRS Khanza (struktur bawaan).
 *
 * - Hanya membaca (SELECT) lewat koneksi "khanza", dengan parameter terikat untuk kata pencarian.
 * - Tidak disimpan di cache karena berisi data rekam medis.
 * - Setiap bagian dibaca terpisah: bila sebuah tabel atau kolom berbeda di versi Khanza Anda, hanya bagian itu
 *   yang gagal (dicatat di $errors), bagian lain tetap tampil.
 */
class KhanzaPatients
{
    /** @var array<string, string> bagian => pesan galat */
    public array $errors = [];

    protected function db(): ConnectionInterface
    {
        static $prepared = false;
        $db = DB::connection('khanza');
        if (! $prepared) {
            $prepared = true;
            $sec = max(5, (int) config('serenapita.khanza.query_timeout', 30));
            foreach (["SET SESSION max_statement_time = {$sec}", 'SET SESSION max_execution_time = '.($sec * 1000)] as $stmt) {
                try {
                    $db->statement($stmt);
                } catch (Throwable) {
                }
            }
        }

        return $db;
    }

    /** Jalankan satu bagian; gagal = dicatat di $errors dan mengembalikan $default. */
    protected function read(string $section, callable $fn, mixed $default = [])
    {
        try {
            return $fn($this->db());
        } catch (Throwable $e) {
            $this->errors[$section] = mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 220);

            return $default;
        }
    }

    protected static function rows($rows): array
    {
        return collect($rows)->map(fn ($r) => (array) $r)->all();
    }

    /* ======================= PENCARIAN ======================= */

    /**
     * Cari pasien lewat No. RM, NIK, No. kartu BPJS, nomor HP, atau nama (boleh sebagian kata).
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term, int $limit = 50): array
    {
        $term = trim(preg_replace('/\s+/', ' ', $term));
        if (mb_strlen($term) < 3) {
            return [];
        }
        $rows = $this->read('pencarian', function ($db) use ($term, $limit) {
            $q = $db->table('pasien as p')->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'p.kd_pj')
                ->select('p.no_rkm_medis', 'p.nm_pasien', 'p.no_ktp', 'p.jk', 'p.tgl_lahir', 'p.alamat', 'p.no_tlp', 'p.no_peserta', 'p.tgl_daftar', 'pj.png_jawab');
            if (preg_match('/^\d+$/', $term)) {
                $q->where(fn ($w) => $w->where('p.no_rkm_medis', $term)->orWhere('p.no_ktp', $term)->orWhere('p.no_peserta', $term)
                    ->orWhere('p.no_rkm_medis', 'like', $term.'%')->orWhere('p.no_tlp', 'like', '%'.$term.'%'));
            } else {
                $q->where('p.nm_pasien', 'like', '%'.str_replace(' ', '%', $term).'%');
            }

            return self::rows($q->orderBy('p.nm_pasien')->limit($limit)->get());
        });

        return $this->withLastVisit($rows);
    }

    /** Tambahkan jumlah kunjungan dan tanggal kunjungan terakhir untuk daftar pasien. */
    protected function withLastVisit(array $rows): array
    {
        if (! $rows) {
            return [];
        }
        $stats = $this->read('kunjungan terakhir', fn ($db) => $db->table('reg_periksa')
            ->whereIn('no_rkm_medis', array_column($rows, 'no_rkm_medis'))->where('stts', '<>', 'Batal')
            ->groupBy('no_rkm_medis')->selectRaw('no_rkm_medis, COUNT(*) AS kunjungan, MAX(tgl_registrasi) AS terakhir')
            ->get()->keyBy('no_rkm_medis'), collect());

        return array_map(fn ($r) => $r + ['kunjungan' => (int) ($stats[$r['no_rkm_medis']]->kunjungan ?? 0), 'terakhir' => $stats[$r['no_rkm_medis']]->terakhir ?? null], $rows);
    }

    /**
     * Pasien yang terdaftar pada satu tanggal (default hari ini), untuk tampilan awal.
     *
     * @return array<int, array<string, mixed>>
     */
    public function registrations(string $date): array
    {
        return $this->read('kunjungan hari ini', fn ($db) => self::rows($db->table('reg_periksa as rp')
            ->join('pasien as p', 'p.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'rp.kd_dokter')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->where('rp.tgl_registrasi', $date)
            ->select('rp.no_rawat', 'rp.jam_reg', 'rp.no_reg', 'rp.status_lanjut', 'rp.stts', 'p.no_rkm_medis', 'p.nm_pasien', 'p.jk', 'p.tgl_lahir', 'pl.nm_poli', 'd.nm_dokter', 'pj.png_jawab')
            ->orderBy('rp.jam_reg')->limit(500)->get()));
    }

    /* ======================= PROFIL PASIEN ======================= */

    /** @return array<string, mixed>|null */
    public function patient(string $rm): ?array
    {
        $p = $this->read('identitas', fn ($db) => (array) ($db->table('pasien')->where('no_rkm_medis', $rm)->first() ?? []), null);
        if (! $p) {
            return null;
        }
        // Nama wilayah dan penjamin dibaca terpisah supaya identitas tetap tampil walau tabel wilayah berbeda
        $name = fn (string $table, string $key, string $col, $val) => $val === null || $val === '' ? null
            : $this->read('wilayah', fn ($db) => $db->table($table)->where($key, $val)->value($col), null);
        $p['_kelurahan'] = $name('kelurahan', 'kd_kel', 'nm_kel', $p['kd_kel'] ?? null);
        $p['_kecamatan'] = $name('kecamatan', 'kd_kec', 'nm_kec', $p['kd_kec'] ?? null);
        $p['_kabupaten'] = $name('kabupaten', 'kd_kab', 'nm_kab', $p['kd_kab'] ?? null);
        $p['_penjamin'] = $name('penjab', 'kd_pj', 'png_jawab', $p['kd_pj'] ?? null);

        return $p;
    }

    /**
     * Semua kunjungan pasien (terbaru dulu), dengan diagnosa utama dan ruang rawat inap.
     *
     * @return array<int, array<string, mixed>>
     */
    public function visits(string $rm): array
    {
        $visits = $this->read('riwayat kunjungan', fn ($db) => self::rows($db->table('reg_periksa as rp')
            ->leftJoin('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'rp.kd_dokter')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->where('rp.no_rkm_medis', $rm)
            ->select('rp.no_rawat', 'rp.tgl_registrasi', 'rp.jam_reg', 'rp.status_lanjut', 'rp.stts', 'rp.status_bayar', 'pl.nm_poli', 'd.nm_dokter', 'pj.png_jawab')
            ->orderByDesc('rp.tgl_registrasi')->orderByDesc('rp.jam_reg')->limit(500)->get()));
        if (! $visits) {
            return [];
        }
        $ids = array_column($visits, 'no_rawat');
        $dx = $this->read('diagnosa', fn ($db) => $db->table('diagnosa_pasien as dp')->leftJoin('penyakit as py', 'py.kd_penyakit', '=', 'dp.kd_penyakit')
            ->whereIn('dp.no_rawat', $ids)->orderBy('dp.prioritas')->get(['dp.no_rawat', 'dp.kd_penyakit', 'py.nm_penyakit'])
            ->groupBy('no_rawat'), collect());
        $rooms = $this->read('rawat inap', fn ($db) => $db->table('kamar_inap as ki')->leftJoin('kamar as k', 'k.kd_kamar', '=', 'ki.kd_kamar')
            ->leftJoin('bangsal as b', 'b.kd_bangsal', '=', 'k.kd_bangsal')->whereIn('ki.no_rawat', $ids)
            ->orderBy('ki.tgl_masuk')->get(['ki.no_rawat', 'b.nm_bangsal', 'ki.tgl_masuk', 'ki.tgl_keluar', 'ki.stts_pulang'])
            ->groupBy('no_rawat'), collect());

        return array_map(function ($v) use ($dx, $rooms) {
            $d = $dx[$v['no_rawat']] ?? collect();
            $r = $rooms[$v['no_rawat']] ?? collect();

            return $v + [
                'diagnosa' => $d->map(fn ($x) => trim($x->kd_penyakit.' '.$x->nm_penyakit))->all(),
                'bangsal' => $r->pluck('nm_bangsal')->filter()->unique()->implode(', '),
                'keluar' => $r->last()?->tgl_keluar,
                'stts_pulang' => $r->last()?->stts_pulang,
            ];
        }, $visits);
    }

    /**
     * Ringkasan klinis: alergi tercatat, tanda vital terakhir, dan diagnosa yang paling sering.
     *
     * @param  array<int, array<string, mixed>>  $visits
     * @return array{alergi: string[], vital: ?array, sering: array<string, int>}
     */
    public function clinicalSummary(string $rm, array $visits): array
    {
        $empty = ['', '-', 'TIDAK ADA', 'TIDAK', 'TAK', 'NO', 'NIHIL', 'DISANGKAL', 'TIDAK ADA ALERGI'];
        $alergi = $this->read('alergi', fn ($db) => $db->table('pemeriksaan_ralan as pr')->join('reg_periksa as rp', 'rp.no_rawat', '=', 'pr.no_rawat')
            ->where('rp.no_rkm_medis', $rm)->whereNotNull('pr.alergi')->orderByDesc('pr.tgl_perawatan')->limit(200)->pluck('pr.alergi')
            ->map(fn ($a) => trim((string) $a))->reject(fn ($a) => in_array(mb_strtoupper($a), $empty, true))->unique()->values()->all());
        $vital = $this->read('tanda vital', fn ($db) => (array) ($db->table('pemeriksaan_ralan as pr')->join('reg_periksa as rp', 'rp.no_rawat', '=', 'pr.no_rawat')
            ->where('rp.no_rkm_medis', $rm)->orderByDesc('pr.tgl_perawatan')->orderByDesc('pr.jam_rawat')->select('pr.*')->first() ?? []), []);
        $sering = collect($visits)->flatMap(fn ($v) => $v['diagnosa'])->countBy()->sortDesc()->take(5)->all();

        return ['alergi' => $alergi, 'vital' => $vital ?: null, 'sering' => $sering];
    }

    /* ======================= DETAIL KUNJUNGAN ======================= */

    /** @return array<string, mixed>|null */
    public function visit(string $rm, string $noRawat): ?array
    {
        $v = $this->read('kunjungan', fn ($db) => (array) ($db->table('reg_periksa as rp')
            ->leftJoin('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
            ->leftJoin('dokter as d', 'd.kd_dokter', '=', 'rp.kd_dokter')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->where('rp.no_rawat', $noRawat)->where('rp.no_rkm_medis', $rm)
            ->select('rp.*', 'pl.nm_poli', 'd.nm_dokter', 'pj.png_jawab')->first() ?? []), null);
        if (! $v) {
            return null;
        }
        $id = $noRawat;
        $byDate = fn (Collection $c, string $date, string $time) => $c->sortBy(fn ($r) => ($r[$date] ?? '').' '.($r[$time] ?? ''))->values()->all();

        $soap = collect();
        foreach (['pemeriksaan_ralan' => 'Rawat jalan', 'pemeriksaan_ranap' => 'Rawat inap'] as $table => $label) {
            $soap = $soap->merge($this->read('pemeriksaan ('.strtolower($label).')', fn ($db) => collect(self::rows($db->table($table)->where('no_rawat', $id)->get()))
                ->map(fn ($r) => $r + ['_jenis' => $label]), collect()));
        }
        $staff = $this->names($soap->pluck('nip')->filter()->unique()->values()->all());

        $tindakan = collect();
        foreach ([['rawat_jl_dr', 'jns_perawatan', 'Dokter'], ['rawat_jl_pr', 'jns_perawatan', 'Perawat'], ['rawat_jl_drpr', 'jns_perawatan', 'Dokter & perawat'],
            ['rawat_inap_dr', 'jns_perawatan_inap', 'Dokter'], ['rawat_inap_pr', 'jns_perawatan_inap', 'Perawat'], ['rawat_inap_drpr', 'jns_perawatan_inap', 'Dokter & perawat']] as [$table, $ref, $by]) {
            $tindakan = $tindakan->merge($this->read('tindakan', fn ($db) => collect(self::rows($db->table($table.' as t')->leftJoin($ref.' as j', 'j.kd_jenis_prw', '=', 't.kd_jenis_prw')
                ->where('t.no_rawat', $id)->select('t.tgl_perawatan', 't.jam_rawat', 't.kd_jenis_prw', 'j.nm_perawatan')->get()))
                ->map(fn ($r) => $r + ['_oleh' => $by]), collect()));
        }

        $obat = $this->read('obat', fn ($db) => self::rows($db->table('detail_pemberian_obat as o')->leftJoin('databarang as b', 'b.kode_brng', '=', 'o.kode_brng')
            ->where('o.no_rawat', $id)->orderBy('o.tgl_perawatan')->orderBy('o.jam')
            ->select('o.tgl_perawatan', 'o.jam', 'o.kode_brng', 'o.jml', 'b.nama_brng', 'b.kode_sat')->get()));
        $aturan = $this->read('aturan pakai', fn ($db) => $db->table('aturan_pakai')->where('no_rawat', $id)->get()
            ->mapWithKeys(fn ($a) => [$a->tgl_perawatan.'|'.$a->jam.'|'.$a->kode_brng => $a->aturan])->all());
        $obat = array_map(fn ($o) => $o + ['aturan' => $aturan[$o['tgl_perawatan'].'|'.$o['jam'].'|'.$o['kode_brng']] ?? null], $obat);

        $lab = $this->read('laboratorium', fn ($db) => self::rows($db->table('detail_periksa_lab as dl')
            ->leftJoin('template_laboratorium as t', 't.id_template', '=', 'dl.id_template')
            ->leftJoin('jns_perawatan_lab as j', 'j.kd_jenis_prw', '=', 'dl.kd_jenis_prw')
            ->where('dl.no_rawat', $id)->orderBy('dl.tgl_periksa')->orderBy('dl.jam')->orderBy('dl.kd_jenis_prw')
            ->select('dl.tgl_periksa', 'dl.jam', 'j.nm_perawatan', 't.Pemeriksaan as pemeriksaan', 'dl.nilai', 't.satuan', 'dl.nilai_rujukan', 'dl.keterangan')->get()));
        $rad = $this->read('radiologi', fn ($db) => self::rows($db->table('periksa_radiologi as r')->leftJoin('jns_perawatan_radiologi as j', 'j.kd_jenis_prw', '=', 'r.kd_jenis_prw')
            ->where('r.no_rawat', $id)->orderBy('r.tgl_periksa')->select('r.tgl_periksa', 'r.jam', 'j.nm_perawatan')->get()));
        $radHasil = $this->read('hasil radiologi', fn ($db) => self::rows($db->table('hasil_radiologi')->where('no_rawat', $id)->orderBy('tgl_periksa')->get()));

        return [
            'info' => $v,
            'diagnosa' => $this->read('diagnosa', fn ($db) => self::rows($db->table('diagnosa_pasien as dp')->leftJoin('penyakit as py', 'py.kd_penyakit', '=', 'dp.kd_penyakit')
                ->where('dp.no_rawat', $id)->orderBy('dp.prioritas')->select('dp.kd_penyakit', 'py.nm_penyakit', 'dp.status', 'dp.prioritas')->get())),
            'prosedur' => $this->read('prosedur', fn ($db) => self::rows($db->table('prosedur_pasien as pp')->leftJoin('icd9 as i', 'i.kode', '=', 'pp.kode')
                ->where('pp.no_rawat', $id)->orderBy('pp.prioritas')->select('pp.kode', 'i.deskripsi_panjang', 'pp.status')->get())),
            'soap' => $byDate($soap->map(fn ($r) => $r + ['_petugas' => $staff[$r['nip'] ?? ''] ?? ($r['nip'] ?? null)]), 'tgl_perawatan', 'jam_rawat'),
            'tindakan' => $byDate($tindakan, 'tgl_perawatan', 'jam_rawat'),
            'obat' => $obat,
            'lab' => $lab,
            'radiologi' => $rad,
            'radiologi_hasil' => $radHasil,
            'kamar' => $this->read('rawat inap', fn ($db) => self::rows($db->table('kamar_inap as ki')->leftJoin('kamar as k', 'k.kd_kamar', '=', 'ki.kd_kamar')
                ->leftJoin('bangsal as b', 'b.kd_bangsal', '=', 'k.kd_bangsal')->where('ki.no_rawat', $id)->orderBy('ki.tgl_masuk')->orderBy('ki.jam_masuk')
                ->select('ki.kd_kamar', 'b.nm_bangsal', 'ki.tgl_masuk', 'ki.jam_masuk', 'ki.tgl_keluar', 'ki.jam_keluar', 'ki.lama', 'ki.diagnosa_awal', 'ki.diagnosa_akhir', 'ki.stts_pulang')->get())),
            'operasi' => $this->read('operasi', fn ($db) => self::rows($db->table('laporan_operasi')->where('no_rawat', $id)->orderBy('tanggal')->get())),
            'resume' => collect(['resume_pasien' => 'Resume rawat jalan', 'resume_pasien_ranap' => 'Resume rawat inap'])
                ->map(fn ($label, $table) => $this->read(strtolower($label), fn ($db) => (array) ($db->table($table)->where('no_rawat', $id)->first() ?? []), []))
                ->filter()->all(),
        ];
    }

    /** Nama petugas dari NIP (tabel pegawai), untuk kolom "diperiksa oleh". */
    protected function names(array $nips): array
    {
        return $nips ? $this->read('nama petugas', fn ($db) => $db->table('pegawai')->whereIn('nik', $nips)->pluck('nama', 'nik')->all()) : [];
    }

    /** Umur dalam tahun dan bulan, misalnya "34 th 2 bl". */
    public static function age(?string $birth, ?string $at = null): string
    {
        if (! $birth || str_starts_with($birth, '0000')) {
            return '—';
        }
        try {
            $diff = Carbon::parse($birth)->diff($at ? Carbon::parse($at) : now());
        } catch (Throwable) {
            return '—';
        }

        return $diff->y > 0 ? $diff->y.' th'.($diff->m ? ' '.$diff->m.' bl' : '') : ($diff->m ? $diff->m.' bl' : $diff->d.' hr');
    }
}
