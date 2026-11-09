<?php

namespace App\Services\Khanza;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Mengambil data akuntansi SIMRS Khanza sampai tanggal cut off untuk closing (hanya SELECT, tanpa cache).
 *
 * Sumber buku besar: tabel bawaan Khanza rekening, rekeningtahun, jurnal, detailjurnal.
 * Sumber rincian (untuk dicocokkan dengan buku besar): piutang_pasien + bayar_piutang, pemesanan + bayar_pemesanan,
 * ipsrspemesanan + bayar_pemesanan_non_medis, riwayat_barang_medis, reg_periksa, kamar_inap.
 *
 * Nilai saldo disimpan bertanda: debet − kredit (positif = saldo debet, negatif = saldo kredit).
 * Bila satu tabel berbeda di versi Khanza Anda, hanya dataset itu yang gagal dan dicatat di $errors.
 */
class KhanzaClosing
{
    /** Kode rekening buatan untuk akumulasi laba rugi sebelum awal tahun (saat saldo awal dihitung dari jurnal). */
    public const PRIOR_PL = '#LABA-LALU';

    public const OPENINGS = [
        'rekeningtahun' => 'Saldo awal tahun di Khanza (tabel rekeningtahun)',
        'jurnal' => 'Akumulasi semua jurnal Khanza sebelum awal tahun',
        'kosong' => 'Tanpa saldo awal (diisi lewat jurnal penyesuaian)',
    ];

    public array $errors = [];

    protected string $from;
    protected string $to;
    protected string $year;

    public function __construct(string $yearStart, string $cutoff, protected string $opening = 'rekeningtahun')
    {
        $this->from = Carbon::parse($yearStart)->toDateString();
        $this->to = Carbon::parse($cutoff)->toDateString();
        $this->year = substr($this->from, 0, 4);
    }

    protected function db()
    {
        return DB::connection('khanza');
    }

    protected function select(string $key, string $sql, array $bind = []): ?array
    {
        try {
            return array_map(fn ($r) => (array) $r, $this->db()->select($sql, $bind));
        } catch (Throwable $e) {
            $this->errors[$key] = mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 300);

            return null;
        }
    }

    /** Coba beberapa versi query (struktur tabel Khanza berbeda antar versi); yang pertama berhasil dipakai. */
    protected function first(string $key, array $sqls, array $bind = []): array
    {
        foreach ($sqls as $sql) {
            $rows = $this->select($key, $sql, $bind);
            if ($rows !== null) {
                unset($this->errors[$key]);

                return $rows;
            }
        }

        return [];
    }

    protected function prepare(): void
    {
        $sec = max(30, (int) config('closing.query_timeout', 180));
        foreach (["SET SESSION max_statement_time = {$sec}", 'SET SESSION max_execution_time = '.($sec * 1000)] as $stmt) {
            try {
                $this->db()->statement($stmt);
            } catch (Throwable) {
            }
        }
    }

    /** @return array<string, array> semua dataset closing */
    public function collect(): array
    {
        @set_time_limit(0);
        $this->prepare();

        $admitted = $this->stillAdmitted();
        $inpatients = array_flip(array_column($admitted, 'no_rawat'));

        return [
            'akun' => $this->accounts(),
            'mutasi' => $this->movements(),
            'tidak_seimbang' => $this->unbalanced(),
            'setelah_cutoff' => $this->afterCutoff(),
            'piutang' => $this->receivables(),
            'hutang' => $this->payables(),
            'persediaan' => $this->inventory(),
            'belum_ditagih' => array_values(array_filter($this->unbilled(), fn ($r) => ! isset($inpatients[$r['no_rawat']]))),
            'dirawat' => $admitted,
            'lanjutan' => $this->carryOver(array_keys($inpatients)),
        ];
    }

    /** Daftar rekening Khanza beserta saldo awal tahun (bertanda D − K). Rekening yang dipakai jurnal tapi tidak terdaftar ikut ditambahkan. */
    public function accounts(): array
    {
        $rek = $this->select('rekening', 'SELECT kd_rek, nm_rek, tipe, balance FROM rekening ORDER BY kd_rek') ?? [];
        $out = [];
        foreach ($rek as $r) {
            $out[$r['kd_rek']] = ['kd_rek' => (string) $r['kd_rek'], 'nm_rek' => (string) $r['nm_rek'], 'tipe' => (string) $r['tipe'], 'balance' => (string) $r['balance'], 'awal' => 0.0, 'terdaftar' => true];
        }
        $add = function (string $kd, float $signed) use (&$out) {
            $out[$kd] ??= ['kd_rek' => $kd, 'nm_rek' => '', 'tipe' => '', 'balance' => '', 'awal' => 0.0, 'terdaftar' => false];
            $out[$kd]['awal'] = round($out[$kd]['awal'] + $signed, 2);
        };

        if ($this->opening === 'rekeningtahun') {
            foreach ($this->select('saldo_awal', 'SELECT kd_rek, SUM(saldo_awal) AS saldo FROM rekeningtahun WHERE thn = ? GROUP BY kd_rek', [$this->year]) ?? [] as $r) {
                $bal = $out[$r['kd_rek']]['balance'] ?? 'D';
                $add((string) $r['kd_rek'], ($bal === 'K' ? -1 : 1) * (float) $r['saldo']);
            }
        } elseif ($this->opening === 'jurnal') {
            $prior = $this->select('saldo_awal', 'SELECT d.kd_rek, SUM(d.debet) AS debet, SUM(d.kredit) AS kredit
                FROM detailjurnal d JOIN jurnal j ON j.no_jurnal = d.no_jurnal
                WHERE j.tgl_jurnal < ? GROUP BY d.kd_rek', [$this->from]) ?? [];
            foreach ($prior as $r) {
                $net = (float) $r['debet'] - (float) $r['kredit'];
                if (($out[$r['kd_rek']]['tipe'] ?? '') === 'R') {
                    $add(self::PRIOR_PL, $net); // laba rugi tahun lalu belum ditutup → dipetakan ke saldo laba ditahan
                } else {
                    $add((string) $r['kd_rek'], $net);
                }
            }
            if (isset($out[self::PRIOR_PL])) {
                $out[self::PRIOR_PL] = ['nm_rek' => 'Akumulasi laba/rugi sebelum '.Carbon::parse($this->from)->translatedFormat('d M Y').' (dari jurnal Khanza)', 'tipe' => 'M', 'balance' => 'K', 'terdaftar' => true] + $out[self::PRIOR_PL];
            }
        }
        // Rekening yang dipakai jurnal tahun berjalan (termasuk setelah cut off) tetapi tidak ada di tabel rekening
        foreach ($this->select('rekening_tidak_terdaftar', 'SELECT DISTINCT d.kd_rek FROM detailjurnal d JOIN jurnal j ON j.no_jurnal = d.no_jurnal
                WHERE j.tgl_jurnal >= ?', [$this->from]) ?? [] as $r) {
            if (! isset($out[$r['kd_rek']])) {
                $add((string) $r['kd_rek'], 0);
            }
        }

        return array_values($out);
    }

    /** Mutasi debet/kredit per rekening per bulan, dari awal tahun sampai cut off. */
    public function movements(): array
    {
        return array_map(fn ($r) => ['kd_rek' => (string) $r['kd_rek'], 'bulan' => (string) $r['bulan'], 'debet' => round((float) $r['debet'], 2), 'kredit' => round((float) $r['kredit'], 2)],
            $this->select('mutasi', 'SELECT d.kd_rek, SUBSTR(j.tgl_jurnal, 1, 7) AS bulan, SUM(d.debet) AS debet, SUM(d.kredit) AS kredit
                FROM detailjurnal d JOIN jurnal j ON j.no_jurnal = d.no_jurnal
                WHERE j.tgl_jurnal BETWEEN ? AND ?
                GROUP BY d.kd_rek, SUBSTR(j.tgl_jurnal, 1, 7)', [$this->from, $this->to]) ?? []);
    }

    /** Jurnal yang total debet ≠ total kredit. */
    public function unbalanced(): array
    {
        return $this->select('tidak_seimbang', 'SELECT j.no_jurnal, j.no_bukti, j.tgl_jurnal, j.keterangan, SUM(d.debet) AS debet, SUM(d.kredit) AS kredit
            FROM jurnal j JOIN detailjurnal d ON d.no_jurnal = j.no_jurnal
            WHERE j.tgl_jurnal BETWEEN ? AND ?
            GROUP BY j.no_jurnal, j.no_bukti, j.tgl_jurnal, j.keterangan
            HAVING ABS(SUM(d.debet) - SUM(d.kredit)) >= 1
            ORDER BY j.tgl_jurnal LIMIT 3000', [$this->from, $this->to]) ?? [];
    }

    /** Transaksi Khanza yang masih terjadi setelah cut off (seharusnya sudah di SIMRS baru). */
    public function afterCutoff(): array
    {
        $j = $this->select('setelah_cutoff', 'SELECT COUNT(DISTINCT j.no_jurnal) AS jurnal, COALESCE(SUM(d.debet), 0) AS nilai, MAX(j.tgl_jurnal) AS terakhir
            FROM jurnal j JOIN detailjurnal d ON d.no_jurnal = j.no_jurnal WHERE j.tgl_jurnal > ?', [$this->to])[0] ?? [];
        $k = $this->select('kunjungan_setelah_cutoff', "SELECT COUNT(*) AS kunjungan, MAX(tgl_registrasi) AS terakhir FROM reg_periksa WHERE tgl_registrasi > ? AND stts <> 'Batal'", [$this->to])[0] ?? [];

        return [[
            'jurnal' => (int) ($j['jurnal'] ?? 0), 'nilai' => round((float) ($j['nilai'] ?? 0), 2), 'jurnal_terakhir' => $j['terakhir'] ?? null,
            'kunjungan' => (int) ($k['kunjungan'] ?? 0), 'kunjungan_terakhir' => $k['terakhir'] ?? null,
        ]];
    }

    /** Rincian piutang pasien yang masih terbuka per cut off. */
    public function receivables(): array
    {
        $pay = fn (string $amount) => "SELECT no_rawat, SUM({$amount}) AS bayar FROM bayar_piutang WHERE tgl_bayar <= ? GROUP BY no_rawat";
        $sql = fn (string $amount) => "SELECT p.no_rawat, p.no_rkm_medis, ps.nm_pasien, COALESCE(pj.png_jawab, rp.kd_pj) AS penjamin,
                p.tgl_piutang, p.tgltempo, p.totalpiutang, COALESCE(p.uangmuka, 0) AS uangmuka, COALESCE(b.bayar, 0) AS bayar
            FROM piutang_pasien p
            LEFT JOIN reg_periksa rp ON rp.no_rawat = p.no_rawat
            LEFT JOIN pasien ps ON ps.no_rkm_medis = p.no_rkm_medis
            LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
            LEFT JOIN ({$pay($amount)}) b ON b.no_rawat = p.no_rawat
            WHERE p.tgl_piutang <= ? AND p.totalpiutang - COALESCE(p.uangmuka, 0) - COALESCE(b.bayar, 0) >= 1
            ORDER BY p.tgl_piutang";
        $rows = $this->first('piutang', [
            $sql('besar_cicilan + COALESCE(diskon_piutang, 0) + COALESCE(tidak_terbayar, 0)'),
            $sql('besar_cicilan'), // Khanza versi lama tanpa kolom diskon/tidak terbayar
        ], [$this->to, $this->to]);

        return array_map(fn ($r) => [
            'no_rawat' => $r['no_rawat'], 'no_rkm_medis' => $r['no_rkm_medis'], 'pasien' => $r['nm_pasien'], 'penjamin' => (string) $r['penjamin'],
            'tgl_piutang' => $r['tgl_piutang'], 'jatuh_tempo' => $r['tgltempo'], 'piutang' => round((float) $r['totalpiutang'], 2),
            'uang_muka' => round((float) $r['uangmuka'], 2), 'dibayar' => round((float) $r['bayar'], 2),
            'sisa' => round((float) $r['totalpiutang'] - (float) $r['uangmuka'] - (float) $r['bayar'], 2),
        ], $rows);
    }

    /** Rincian hutang supplier (obat/BHP dan non medis) yang belum lunas per cut off. */
    public function payables(): array
    {
        $part = fn (string $label, string $order, string $sup, string $pay) => "SELECT '{$label}' AS jenis, p.no_faktur, p.tgl_pesan, p.tgl_faktur, p.tgl_tempo,
                COALESCE(s.nama_suplier, p.kode_suplier) AS supplier, p.tagihan, COALESCE(b.bayar, 0) AS bayar
            FROM {$order} p
            LEFT JOIN {$sup} s ON s.kode_suplier = p.kode_suplier
            LEFT JOIN (SELECT no_faktur, SUM(besar_bayar) AS bayar FROM {$pay} WHERE tgl_bayar <= ? GROUP BY no_faktur) b ON b.no_faktur = p.no_faktur
            WHERE p.tgl_pesan <= ? AND p.tagihan - COALESCE(b.bayar, 0) >= 1";
        $rows = array_merge(
            $this->select('hutang_medis', $part('Medis', 'pemesanan', 'datasuplier', 'bayar_pemesanan'), [$this->to, $this->to]) ?? [],
            $this->select('hutang_non_medis', $part('Non medis', 'ipsrspemesanan', 'ipsrssuplier', 'bayar_pemesanan_non_medis'), [$this->to, $this->to]) ?? [],
        );

        return array_map(fn ($r) => [
            'jenis' => $r['jenis'], 'no_faktur' => $r['no_faktur'], 'supplier' => (string) $r['supplier'], 'tgl_terima' => $r['tgl_pesan'],
            'tgl_faktur' => $r['tgl_faktur'], 'jatuh_tempo' => $r['tgl_tempo'], 'tagihan' => round((float) $r['tagihan'], 2),
            'dibayar' => round((float) $r['bayar'], 2), 'sisa' => round((float) $r['tagihan'] - (float) $r['bayar'], 2),
        ], $rows);
    }

    /**
     * Nilai persediaan obat/BHP per cut off: stok akhir terakhir di riwayat_barang_medis pada/sebelum cut off,
     * per barang, per depo, per batch, dikali harga beli di databarang (perkiraan).
     */
    public function inventory(): array
    {
        $last = fn (string $cols, string $group, string $join) => "SELECT r.kd_bangsal, r.kode_brng, SUM(r.stok_akhir) AS stok
            FROM riwayat_barang_medis r
            JOIN (SELECT {$cols}, MAX(CONCAT(tanggal, ' ', jam)) AS w FROM riwayat_barang_medis WHERE tanggal <= ? GROUP BY {$group}) l
              ON {$join} AND CONCAT(r.tanggal, ' ', r.jam) = l.w
            GROUP BY r.kd_bangsal, r.kode_brng";
        $stock = $this->first('persediaan', [
            // per batch dan faktur (Khanza dengan stok per batch)
            $last("kode_brng, kd_bangsal, COALESCE(no_batch, '') AS nb, COALESCE(no_faktur, '') AS nf",
                "kode_brng, kd_bangsal, COALESCE(no_batch, ''), COALESCE(no_faktur, '')",
                "l.kode_brng = r.kode_brng AND l.kd_bangsal = r.kd_bangsal AND l.nb = COALESCE(r.no_batch, '') AND l.nf = COALESCE(r.no_faktur, '')"),
            // Khanza versi lama tanpa batch
            $last('kode_brng, kd_bangsal', 'kode_brng, kd_bangsal', 'l.kode_brng = r.kode_brng AND l.kd_bangsal = r.kd_bangsal'),
        ], [$this->to]);
        if (! $stock) {
            return [];
        }
        $items = [];
        foreach ($this->first('databarang', [
            'SELECT b.kode_brng, b.nama_brng, b.kode_sat, b.h_beli, COALESCE(j.nama, b.kdjns) AS jenis FROM databarang b LEFT JOIN jenis j ON j.kdjns = b.kdjns',
            'SELECT kode_brng, nama_brng, kode_sat, h_beli, kdjns AS jenis FROM databarang',
        ]) as $b) {
            $items[$b['kode_brng']] = $b;
        }
        $wards = [];
        foreach ($this->select('bangsal', 'SELECT kd_bangsal, nm_bangsal FROM bangsal') ?? [] as $w) {
            $wards[$w['kd_bangsal']] = $w['nm_bangsal'];
        }
        $out = [];
        foreach ($stock as $s) {
            $qty = (float) $s['stok'];
            if (abs($qty) < 0.0001) {
                continue;
            }
            $b = $items[$s['kode_brng']] ?? [];
            $price = (float) ($b['h_beli'] ?? 0);
            $out[] = [
                'depo' => $wards[$s['kd_bangsal']] ?? (string) $s['kd_bangsal'], 'kode_brng' => $s['kode_brng'], 'nama' => (string) ($b['nama_brng'] ?? ''),
                'jenis' => (string) ($b['jenis'] ?? ''), 'satuan' => (string) ($b['kode_sat'] ?? ''), 'stok' => round($qty, 2),
                'harga_beli' => round($price, 2), 'nilai' => round($qty * $price, 2),
            ];
        }

        return $out;
    }

    /**
     * Kunjungan sampai cut off yang per tanggal cut off belum ada nota dan belum menjadi piutang.
     * Status bayar dibaca per cut off: nota/piutang yang dibuat setelah cut off dianggap belum ada.
     */
    public function unbilled(): array
    {
        $none = fn (string $op) => "NOT EXISTS (SELECT 1 FROM piutang_pasien x WHERE x.no_rawat = rp.no_rawat AND x.tgl_piutang {$op} ?)
              AND NOT EXISTS (SELECT 1 FROM nota_jalan x WHERE x.no_rawat = rp.no_rawat AND x.tanggal {$op} ?)
              AND NOT EXISTS (SELECT 1 FROM nota_inap x WHERE x.no_rawat = rp.no_rawat AND x.tanggal {$op} ?)";

        return $this->select('belum_ditagih', "SELECT rp.no_rawat, rp.tgl_registrasi, rp.no_rkm_medis, ps.nm_pasien AS pasien, rp.status_lanjut AS layanan,
                COALESCE(pl.nm_poli, rp.kd_poli) AS poli, COALESCE(pj.png_jawab, rp.kd_pj) AS penjamin, rp.stts AS status_periksa
            FROM reg_periksa rp
            LEFT JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
            LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
            WHERE rp.tgl_registrasi BETWEEN ? AND ? AND rp.stts <> 'Batal'
              AND {$none('<=')}
              AND (rp.status_bayar = 'Belum Bayar' OR NOT ({$none('>')}))
            ORDER BY rp.tgl_registrasi, rp.no_rawat LIMIT 10000", [$this->from, $this->to, $this->to, $this->to, $this->to, $this->to, $this->to, $this->to]) ?? [];
    }

    /**
     * Pasien rawat inap yang masih dirawat pada akhir tanggal cut off. Setelah go-live SIMRS baru, pasien ini tetap
     * dilayani di Khanza sampai pulang (ranap lanjutan). Ikut dicatat tanggal pulangnya bila sudah pulang.
     */
    public function stillAdmitted(): array
    {
        $rows = $this->select('dirawat', "SELECT ki.no_rawat, MIN(ki.tgl_masuk) AS tgl_masuk, rp.no_rkm_medis, ps.nm_pasien AS pasien,
                COALESCE(pj.png_jawab, rp.kd_pj) AS penjamin, rp.status_bayar,
                MAX(CASE WHEN ki.stts_pulang = '-' THEN 1 ELSE 0 END) AS masih_dirawat,
                MAX(CASE WHEN ki.stts_pulang NOT IN ('-', 'Pindah Kamar') THEN ki.tgl_keluar END) AS tgl_pulang
            FROM kamar_inap ki
            JOIN reg_periksa rp ON rp.no_rawat = ki.no_rawat
            LEFT JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
            WHERE ki.tgl_masuk <= ?
            GROUP BY ki.no_rawat, rp.no_rkm_medis, ps.nm_pasien, pj.png_jawab, rp.kd_pj, rp.status_bayar
            HAVING MAX(CASE WHEN ki.stts_pulang = '-' OR ki.tgl_keluar > ? THEN 1 ELSE 0 END) = 1
            ORDER BY tgl_masuk", [$this->to, $this->to]) ?? [];

        return array_map(fn ($r) => ['masih_dirawat' => (bool) $r['masih_dirawat'], 'tgl_pulang' => $r['masih_dirawat'] ? null : $r['tgl_pulang']] + $r, $rows);
    }

    /**
     * Semua baris jurnal Khanza setelah cut off. Jurnal dikelompokkan "Ranap lanjutan" bila no. bukti atau keterangannya
     * memuat no. rawat pasien yang masih dirawat saat cut off (Khanza memakai no. rawat sebagai no. bukti jurnal tagihan
     * dan pelunasan); selain itu "Lainnya" dan perlu diperiksa Akunting.
     *
     * @param  string[]  $inpatients  no. rawat pasien yang dirawat saat cut off
     */
    public function carryOver(array $inpatients): array
    {
        $rows = $this->select('lanjutan', 'SELECT j.no_jurnal, j.no_bukti, j.tgl_jurnal, j.keterangan, d.kd_rek, SUM(d.debet) AS debet, SUM(d.kredit) AS kredit
            FROM jurnal j JOIN detailjurnal d ON d.no_jurnal = j.no_jurnal
            WHERE j.tgl_jurnal > ?
            GROUP BY j.no_jurnal, j.no_bukti, j.tgl_jurnal, j.keterangan, d.kd_rek
            ORDER BY j.tgl_jurnal, j.no_jurnal LIMIT 50000', [$this->to]) ?? [];
        $set = array_flip($inpatients);

        return array_map(function ($r) use ($set) {
            $rawat = null;
            if (isset($set[trim((string) $r['no_bukti'])])) {
                $rawat = trim((string) $r['no_bukti']);
            } elseif (preg_match_all('#\d{4}/\d{2}/\d{2}/\d{6}#', $r['no_bukti'].' '.$r['keterangan'], $m)) {
                $rawat = collect($m[0])->first(fn ($x) => isset($set[$x]));
            }

            return ['no_jurnal' => $r['no_jurnal'], 'no_bukti' => $r['no_bukti'], 'tgl_jurnal' => $r['tgl_jurnal'], 'keterangan' => $r['keterangan'],
                'kd_rek' => (string) $r['kd_rek'], 'debet' => round((float) $r['debet'], 2), 'kredit' => round((float) $r['kredit'], 2),
                'no_rawat' => $rawat, 'kelompok' => $rawat ? 'Ranap lanjutan' : 'Lainnya'];
        }, $rows);
    }
}
