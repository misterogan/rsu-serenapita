<?php

namespace App\Services\Khanza;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Laporan manajemen yang dibaca langsung dari database SIMRS Khanza (struktur bawaan, tanpa kustomisasi).
 *
 * - Hanya menjalankan SELECT, lewat koneksi "khanza" yang sebaiknya memakai user MySQL khusus baca (GRANT SELECT).
 * - Setiap laporan disimpan sementara (cache) beberapa menit agar tidak membebani server SIMRS.
 * - Bila sebuah tabel atau kolom berbeda di versi Khanza Anda, hanya laporan itu yang gagal; laporan lain tetap tampil.
 * - Salinan semua query untuk dijalankan manual ada di database/khanza/laporan-manajemen.sql.
 */
class KhanzaReports
{
    public string $from;
    public string $to;
    protected string $toEnd;   // akhir hari tanggal selesai, untuk kolom datetime
    protected string $toNext;  // hari setelah tanggal selesai
    public int $days;
    protected bool $fresh = false;
    public array $errors = [];

    public function __construct(string $from, string $to, bool $fresh = false)
    {
        $f = Carbon::parse($from)->startOfDay();
        $t = Carbon::parse($to)->startOfDay();
        if ($t->lt($f)) {
            [$f, $t] = [$t, $f];
        }
        $this->from = $f->toDateString();
        $this->to = $t->toDateString();
        $this->toEnd = $t->toDateString().' 23:59:59';
        $this->toNext = $t->copy()->addDay()->toDateString();
        $this->days = $f->diffInDays($t) + 1;
        $this->fresh = $fresh;
    }

    public static function configured(): bool
    {
        return filled(config('database.connections.khanza.database')) && filled(config('database.connections.khanza.host'));
    }

    /** Uji koneksi: mengembalikan versi server database. */
    public static function ping(): string
    {
        return (string) (DB::connection('khanza')->selectOne('SELECT VERSION() AS v')->v ?? '');
    }

    protected function igd(): string
    {
        return DB::connection('khanza')->getPdo()->quote((string) config('serenapita.khanza.poli_igd', 'IGDK'));
    }

    /** Jalankan query (hanya SELECT) dengan cache; gagal = dicatat di $errors dan mengembalikan []. */
    protected function run(string $key, string $sql): array
    {
        $trim = ltrim($sql);
        if (! preg_match('/^(SELECT|\()/i', $trim)) {
            throw new \RuntimeException('Hanya query SELECT yang diizinkan.');
        }
        static $prepared = false;
        if (! $prepared) {
            $prepared = true;
            $sec = max(5, (int) config('serenapita.khanza.query_timeout', 30));
            // Batasi lama setiap query agar tidak membebani SIMRS (MariaDB / MySQL)
            foreach (["SET SESSION max_statement_time = {$sec}", 'SET SESSION max_execution_time = '.($sec * 1000)] as $stmt) {
                try {
                    DB::connection('khanza')->statement($stmt);
                } catch (Throwable) {
                }
            }
        }
        $cacheKey = 'khanza:'.$key.':'.$this->from.':'.$this->to.':'.md5($sql);
        $minutes = max(1, (int) config('serenapita.khanza.cache_minutes', 10));
        try {
            if ($this->fresh) {
                Cache::forget($cacheKey);
            }

            return Cache::remember($cacheKey, now()->addMinutes($minutes), fn () => array_map(fn ($r) => (array) $r, DB::connection('khanza')->select($sql)));
        } catch (Throwable $e) {
            $this->errors[$key] = mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 220);

            return [];
        }
    }

    protected function one(string $key, string $sql): array
    {
        return $this->run($key, $sql)[0] ?? [];
    }

    /* ======================= LAPORAN ======================= */

    /** Ringkasan kunjungan pada periode. */
    public function summary(?string $from = null, ?string $to = null): array
    {
        $f = $from ?? $this->from;
        $t = $to ?? $this->to;

        return $this->one('ringkasan_'.$f.'_'.$t, "SELECT
                SUM(rp.stts <> 'Batal') AS kunjungan,
                SUM(rp.stts = 'Batal') AS batal,
                SUM(rp.stts <> 'Batal' AND rp.status_lanjut = 'Ralan' AND rp.kd_poli <> {$this->igd()}) AS rawat_jalan,
                SUM(rp.stts <> 'Batal' AND rp.kd_poli = {$this->igd()}) AS igd,
                SUM(rp.stts <> 'Batal' AND rp.status_lanjut = 'Ranap') AS lanjut_ranap,
                SUM(rp.stts <> 'Batal' AND rp.stts_daftar = 'Baru') AS pasien_baru,
                SUM(rp.stts <> 'Batal' AND pj.png_jawab LIKE '%BPJS%') AS bpjs,
                COUNT(DISTINCT CASE WHEN rp.stts <> 'Batal' THEN rp.no_rkm_medis END) AS pasien_unik
            FROM reg_periksa rp
            LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
            WHERE rp.tgl_registrasi BETWEEN '{$f}' AND '{$t}'");
    }

    /** Ringkasan periode sebelumnya dengan panjang yang sama, untuk perbandingan. */
    public function previousSummary(): array
    {
        $len = $this->days;
        $pf = Carbon::parse($this->from)->subDays($len)->toDateString();
        $pt = Carbon::parse($this->from)->subDay()->toDateString();

        return $this->summary($pf, $pt);
    }

    /** Tren kunjungan harian. */
    public function trend(): array
    {
        return $this->run('tren', "SELECT rp.tgl_registrasi AS tanggal,
                SUM(rp.status_lanjut = 'Ralan' AND rp.kd_poli <> {$this->igd()}) AS rawat_jalan,
                SUM(rp.kd_poli = {$this->igd()}) AS igd,
                SUM(rp.status_lanjut = 'Ranap') AS lanjut_ranap,
                COUNT(*) AS total
            FROM reg_periksa rp
            WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY rp.tgl_registrasi ORDER BY rp.tgl_registrasi");
    }

    /** Kunjungan per poliklinik. */
    public function byClinic(): array
    {
        return $this->run('poli', "SELECT pl.nm_poli AS poliklinik,
                SUM(rp.stts <> 'Batal') AS kunjungan,
                SUM(rp.stts <> 'Batal' AND rp.stts_daftar = 'Baru') AS baru,
                SUM(rp.stts <> 'Batal' AND rp.stts_daftar = 'Lama') AS lama,
                SUM(rp.stts <> 'Batal' AND pj.png_jawab LIKE '%BPJS%') AS bpjs,
                SUM(rp.stts = 'Batal') AS batal
            FROM reg_periksa rp
            JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
            LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
            WHERE rp.tgl_registrasi BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY rp.kd_poli, pl.nm_poli ORDER BY kunjungan DESC");
    }

    /** Kunjungan per cara bayar (penjamin). */
    public function byPayer(): array
    {
        return $this->run('penjab', "SELECT COALESCE(pj.png_jawab, rp.kd_pj) AS cara_bayar,
                COUNT(*) AS kunjungan,
                SUM(rp.status_lanjut = 'Ranap') AS lanjut_ranap
            FROM reg_periksa rp
            LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
            WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY rp.kd_pj, pj.png_jawab ORDER BY kunjungan DESC");
    }

    /**
     * Kinerja dokter rawat jalan:
     * - waktu tunggu = jam daftar sampai pemeriksaan pertama (pemeriksaan_ralan)
     * - lama layanan = task 4 sampai task 5 antrean online (hanya pasien yang tercatat di antrean BPJS)
     */
    public function doctors(): array
    {
        $rows = $this->doctorsQuery(true);
        if (isset($this->errors['dokter'])) {
            // Tabel antrean online tidak ada atau berbeda: tampilkan tanpa lama layanan
            unset($this->errors['dokter']);
            $rows = $this->doctorsQuery(false);
        }

        return $rows;
    }

    protected function doctorsQuery(bool $withAntrol): array
    {
        $tk = $withAntrol ? "LEFT JOIN (SELECT t4.no_rawat, TIMESTAMPDIFF(MINUTE, t4.waktu, t5.waktu) AS layan
                       FROM referensi_mobilejkn_bpjs_taskid t4
                       JOIN referensi_mobilejkn_bpjs_taskid t5 ON t5.no_rawat = t4.no_rawat AND t5.taskid = '5'
                       WHERE t4.taskid = '4' AND t4.waktu BETWEEN '{$this->from}' AND '{$this->toEnd}') tk ON tk.no_rawat = rp.no_rawat"
            : 'LEFT JOIN (SELECT NULL AS no_rawat, NULL AS layan) tk ON tk.no_rawat = rp.no_rawat';

        return $this->run($withAntrol ? 'dokter' : 'dokter_tanpa_antrol', "SELECT d.nm_dokter AS dokter,
                MAX(pl.nm_poli) AS poliklinik,
                COUNT(*) AS pasien,
                ROUND(AVG(CASE WHEN TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, ' ', rp.jam_reg), pr.mulai) BETWEEN 0 AND 600
                    THEN TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, ' ', rp.jam_reg), pr.mulai) END)) AS rata_tunggu_menit,
                ROUND(AVG(CASE WHEN tk.layan BETWEEN 0 AND 240 THEN tk.layan END)) AS rata_layanan_menit,
                SUM(tk.layan IS NOT NULL) AS tercatat_antrol
            FROM reg_periksa rp
            JOIN dokter d ON d.kd_dokter = rp.kd_dokter
            LEFT JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
            LEFT JOIN (SELECT no_rawat, MIN(CONCAT(tgl_perawatan, ' ', jam_rawat)) AS mulai FROM pemeriksaan_ralan
                       WHERE tgl_perawatan BETWEEN '{$this->from}' AND '{$this->to}' GROUP BY no_rawat) pr ON pr.no_rawat = rp.no_rawat
            {$tk}
            WHERE rp.stts <> 'Batal' AND rp.status_lanjut = 'Ralan' AND rp.kd_poli <> {$this->igd()}
              AND rp.tgl_registrasi BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY rp.kd_dokter, d.nm_dokter ORDER BY pasien DESC");
    }

    /** Antrean online Mobile JKN: jumlah booking per status. */
    public function antrolStatus(): array
    {
        return $this->run('antrol_status', "SELECT m.status, COUNT(*) AS jumlah
            FROM referensi_mobilejkn_bpjs m
            WHERE m.tanggalperiksa BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY m.status ORDER BY jumlah DESC");
    }

    /** Antrean online per poli BPJS. */
    public function antrolClinic(): array
    {
        return $this->run('antrol_poli', "SELECT COALESCE(mp.nm, m.kodepoli) AS poli,
                COUNT(*) AS booking,
                SUM(m.status = 'Checkin') AS checkin,
                SUM(m.status = 'Batal') AS batal,
                SUM(m.status = 'Belum') AS belum_datang,
                SUM(m.pasienbaru = '1') AS pasien_baru
            FROM referensi_mobilejkn_bpjs m
            LEFT JOIN (SELECT kd_poli_bpjs, MAX(nm_poli_bpjs) AS nm FROM maping_poli_bpjs GROUP BY kd_poli_bpjs) mp ON mp.kd_poli_bpjs = m.kodepoli
            WHERE m.tanggalperiksa BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY m.kodepoli, mp.nm ORDER BY booking DESC");
    }

    /**
     * Rata-rata waktu per tahap antrean (task ID BPJS):
     * 1 mulai tunggu admisi, 2 mulai dilayani admisi, 3 selesai admisi / mulai tunggu poli,
     * 4 mulai dilayani dokter, 5 selesai di poli / mulai tunggu farmasi, 6 mulai racik obat, 7 obat selesai.
     */
    public function antrolTimes(): array
    {
        return $this->one('antrol_waktu', "SELECT COUNT(*) AS pasien,
                ROUND(AVG(CASE WHEN TIMESTAMPDIFF(MINUTE, t1, t2) BETWEEN 0 AND 300 THEN TIMESTAMPDIFF(MINUTE, t1, t2) END)) AS tunggu_admisi,
                ROUND(AVG(CASE WHEN TIMESTAMPDIFF(MINUTE, t2, t3) BETWEEN 0 AND 300 THEN TIMESTAMPDIFF(MINUTE, t2, t3) END)) AS layan_admisi,
                ROUND(AVG(CASE WHEN TIMESTAMPDIFF(MINUTE, t3, t4) BETWEEN 0 AND 600 THEN TIMESTAMPDIFF(MINUTE, t3, t4) END)) AS tunggu_poli,
                ROUND(AVG(CASE WHEN TIMESTAMPDIFF(MINUTE, t4, t5) BETWEEN 0 AND 300 THEN TIMESTAMPDIFF(MINUTE, t4, t5) END)) AS layan_dokter,
                ROUND(AVG(CASE WHEN TIMESTAMPDIFF(MINUTE, t5, t6) BETWEEN 0 AND 300 THEN TIMESTAMPDIFF(MINUTE, t5, t6) END)) AS tunggu_farmasi,
                ROUND(AVG(CASE WHEN TIMESTAMPDIFF(MINUTE, t6, t7) BETWEEN 0 AND 300 THEN TIMESTAMPDIFF(MINUTE, t6, t7) END)) AS layan_farmasi,
                ROUND(AVG(CASE WHEN TIMESTAMPDIFF(MINUTE, t1, COALESCE(t7, t5)) BETWEEN 0 AND 900 THEN TIMESTAMPDIFF(MINUTE, t1, COALESCE(t7, t5)) END)) AS total_di_rs
            FROM (SELECT no_rawat,
                    MAX(CASE WHEN taskid = '1' THEN waktu END) AS t1, MAX(CASE WHEN taskid = '2' THEN waktu END) AS t2,
                    MAX(CASE WHEN taskid = '3' THEN waktu END) AS t3, MAX(CASE WHEN taskid = '4' THEN waktu END) AS t4,
                    MAX(CASE WHEN taskid = '5' THEN waktu END) AS t5, MAX(CASE WHEN taskid = '6' THEN waktu END) AS t6,
                    MAX(CASE WHEN taskid = '7' THEN waktu END) AS t7
                  FROM referensi_mobilejkn_bpjs_taskid
                  WHERE waktu BETWEEN '{$this->from}' AND '{$this->toEnd}'
                  GROUP BY no_rawat) x");
    }

    /** Pasien yang sedang dirawat dan tempat tidur saat ini (tidak bergantung periode). */
    public function inpatientNow(): array
    {
        $r = $this->one('ranap_kini', "SELECT
                (SELECT COUNT(DISTINCT no_rawat) FROM kamar_inap WHERE stts_pulang = '-') AS dirawat,
                (SELECT COUNT(*) FROM kamar WHERE statusdata = '1') AS tempat_tidur,
                (SELECT COUNT(*) FROM kamar WHERE statusdata = '1' AND status = 'ISI') AS terisi");

        return $r;
    }

    /** Okupansi tempat tidur per bangsal dan kelas saat ini. */
    public function wards(): array
    {
        return $this->run('bangsal', "SELECT b.nm_bangsal AS bangsal, k.kelas,
                COUNT(*) AS tempat_tidur, SUM(k.status = 'ISI') AS terisi, SUM(k.status = 'KOSONG') AS kosong,
                SUM(k.status IN ('DIBERSIHKAN', 'DIBOOKING')) AS disiapkan
            FROM kamar k JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
            WHERE k.statusdata = '1'
            GROUP BY b.kd_bangsal, b.nm_bangsal, k.kelas ORDER BY b.nm_bangsal, k.kelas");
    }

    /** Indikator rawat inap Barber-Johnson untuk periode: BOR, ALOS, BTO, TOI, GDR, NDR. */
    public function inpatientIndicators(): array
    {
        $hp = $this->one('ranap_hp', "SELECT SUM(
                    CASE WHEN stts_pulang = 'Pindah Kamar' THEN GREATEST(0, DATEDIFF(LEAST(tgl_keluar, '{$this->toNext}'), GREATEST(tgl_masuk, '{$this->from}')))
                         ELSE GREATEST(1, DATEDIFF(LEAST(CASE WHEN stts_pulang = '-' OR tgl_keluar IS NULL OR tgl_keluar < tgl_masuk THEN '{$this->toNext}' ELSE tgl_keluar END, '{$this->toNext}'), GREATEST(tgl_masuk, '{$this->from}'))) END
                ) AS hari_perawatan
            FROM kamar_inap
            WHERE tgl_masuk <= '{$this->to}' AND (stts_pulang = '-' OR tgl_keluar >= '{$this->from}')");
        $out = $this->one('ranap_keluar', "SELECT COUNT(*) AS keluar, SUM(x.lama_total) AS lama_total,
                SUM(x.meninggal) AS meninggal, SUM(x.meninggal = 1 AND x.lama_total >= 2) AS meninggal_48
            FROM (SELECT ki.no_rawat, SUM(ki.lama) AS lama_total, MAX(d.meninggal) AS meninggal
                  FROM kamar_inap ki
                  JOIN (SELECT no_rawat, MAX(stts_pulang = 'Meninggal') AS meninggal FROM kamar_inap
                        WHERE tgl_keluar BETWEEN '{$this->from}' AND '{$this->to}' AND stts_pulang NOT IN ('-', 'Pindah Kamar')
                        GROUP BY no_rawat) d ON d.no_rawat = ki.no_rawat
                  GROUP BY ki.no_rawat) x");
        $admit = $this->one('ranap_masuk', "SELECT COUNT(DISTINCT no_rawat) AS masuk FROM kamar_inap WHERE tgl_masuk BETWEEN '{$this->from}' AND '{$this->to}'");
        $beds = (int) ($this->inpatientNow()['tempat_tidur'] ?? 0);
        $HP = (float) ($hp['hari_perawatan'] ?? 0);
        $D = (int) ($out['keluar'] ?? 0);
        $t = $this->days;

        return [
            'masuk' => (int) ($admit['masuk'] ?? 0), 'keluar' => $D, 'hari_perawatan' => $HP, 'tempat_tidur' => $beds,
            'bor' => $beds ? round($HP / ($beds * $t) * 100, 1) : null,
            'alos' => $D ? round(((float) $out['lama_total']) / $D, 1) : null,
            'bto' => $beds ? round($D / $beds, 1) : null,
            'toi' => $D ? round(max(0, $beds * $t - $HP) / $D, 1) : null,
            'gdr' => $D ? round(((int) $out['meninggal']) / $D * 1000, 1) : null,
            'ndr' => $D ? round(((int) $out['meninggal_48']) / $D * 1000, 1) : null,
            'meninggal' => (int) ($out['meninggal'] ?? 0),
        ];
    }

    /** Status pulang pasien rawat inap. */
    public function dischargeStatus(): array
    {
        return $this->run('status_pulang', "SELECT stts_pulang AS status_pulang, COUNT(DISTINCT no_rawat) AS pasien
            FROM kamar_inap
            WHERE tgl_keluar BETWEEN '{$this->from}' AND '{$this->to}' AND stts_pulang NOT IN ('-', 'Pindah Kamar')
            GROUP BY stts_pulang ORDER BY pasien DESC");
    }

    /** Pembayaran yang diterima per cara bayar (nota rawat jalan dan rawat inap). */
    public function payments(): array
    {
        return $this->run('pembayaran', "SELECT jenis, nama_bayar AS cara_bayar, COUNT(*) AS nota, SUM(besar_bayar) AS total FROM (
                SELECT 'Rawat jalan' AS jenis, d.nama_bayar, n.no_rawat, d.besar_bayar
                FROM nota_jalan n JOIN detail_nota_jalan d ON d.no_rawat = n.no_rawat
                WHERE n.tanggal BETWEEN '{$this->from}' AND '{$this->to}'
                UNION ALL
                SELECT 'Rawat inap', d.nama_bayar, n.no_rawat, d.besar_bayar
                FROM nota_inap n JOIN detail_nota_inap d ON d.no_rawat = n.no_rawat
                WHERE n.tanggal BETWEEN '{$this->from}' AND '{$this->to}'
            ) p GROUP BY jenis, nama_bayar ORDER BY total DESC");
    }

    /** Piutang pasien (umumnya klaim BPJS dan asuransi) per penjamin. */
    public function receivables(): array
    {
        return $this->run('piutang', "SELECT COALESCE(pj.png_jawab, rp.kd_pj) AS penjamin,
                COUNT(*) AS pasien, SUM(p.totalpiutang) AS total_piutang, SUM(p.sisapiutang) AS sisa_piutang
            FROM piutang_pasien p
            JOIN reg_periksa rp ON rp.no_rawat = p.no_rawat
            LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
            WHERE p.tgl_piutang BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY rp.kd_pj, pj.png_jawab ORDER BY total_piutang DESC");
    }

    /** Komposisi tagihan (billing) per kategori, tanpa baris judul dan subtotal. */
    public function billingCategories(): array
    {
        return $this->run('billing', "SELECT b.status AS kategori, COUNT(DISTINCT b.no_rawat) AS pasien, SUM(b.totalbiaya) AS total
            FROM billing b
            WHERE b.tgl_byr BETWEEN '{$this->from}' AND '{$this->to}'
              AND b.status NOT IN ('-', 'Tagihan') AND b.status NOT LIKE 'Ttl%'
            GROUP BY b.status ORDER BY total DESC");
    }

    /** Pelayanan penunjang: laboratorium, radiologi, operasi, resep. */
    public function support(): array
    {
        return $this->one('penunjang', "SELECT
                (SELECT COUNT(DISTINCT no_rawat) FROM periksa_lab WHERE tgl_periksa BETWEEN '{$this->from}' AND '{$this->to}') AS lab_pasien,
                (SELECT COALESCE(SUM(biaya), 0) FROM periksa_lab WHERE tgl_periksa BETWEEN '{$this->from}' AND '{$this->to}') AS lab_nilai,
                (SELECT COUNT(DISTINCT no_rawat) FROM periksa_radiologi WHERE tgl_periksa BETWEEN '{$this->from}' AND '{$this->to}') AS rad_pasien,
                (SELECT COALESCE(SUM(biaya), 0) FROM periksa_radiologi WHERE tgl_periksa BETWEEN '{$this->from}' AND '{$this->to}') AS rad_nilai,
                (SELECT COUNT(*) FROM operasi WHERE tgl_operasi BETWEEN '{$this->from}' AND '{$this->toEnd}') AS operasi,
                (SELECT COUNT(*) FROM resep_obat WHERE tgl_perawatan BETWEEN '{$this->from}' AND '{$this->to}') AS resep,
                (SELECT COUNT(*) FROM rujuk WHERE tgl_rujuk BETWEEN '{$this->from}' AND '{$this->to}') AS rujukan_keluar");
    }

    /** 10 obat dengan nilai pemakaian terbesar. */
    public function topDrugs(): array
    {
        return $this->run('obat', "SELECT b.nama_brng AS obat, SUM(d.jml) AS jumlah, SUM(d.total) AS nilai
            FROM detail_pemberian_obat d JOIN databarang b ON b.kode_brng = d.kode_brng
            WHERE d.tgl_perawatan BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY d.kode_brng, b.nama_brng ORDER BY nilai DESC LIMIT 10");
    }

    /** 10 diagnosa utama terbanyak (diagnosa prioritas 1). */
    public function topDiagnoses(): array
    {
        return $this->run('diagnosa', "SELECT p.kd_penyakit AS kode, p.nm_penyakit AS diagnosa,
                SUM(dp.status = 'Ralan') AS rawat_jalan, SUM(dp.status = 'Ranap') AS rawat_inap, COUNT(*) AS total
            FROM diagnosa_pasien dp
            JOIN reg_periksa rp ON rp.no_rawat = dp.no_rawat
            JOIN penyakit p ON p.kd_penyakit = dp.kd_penyakit
            WHERE dp.prioritas = 1 AND rp.tgl_registrasi BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY dp.kd_penyakit, p.nm_penyakit ORDER BY total DESC LIMIT 10");
    }

    /** 10 kecamatan asal pasien terbanyak. */
    public function regions(): array
    {
        return $this->run('wilayah', "SELECT COALESCE(k.nm_kec, '-') AS kecamatan, COUNT(*) AS kunjungan
            FROM reg_periksa rp
            JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN kecamatan k ON k.kd_kec = ps.kd_kec
            WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY ps.kd_kec, k.nm_kec ORDER BY kunjungan DESC LIMIT 10");
    }

    /** Kunjungan menurut jenis kelamin dan kelompok umur. */
    public function demography(): array
    {
        return $this->run('demografi', "SELECT
                CASE WHEN rp.sttsumur IN ('Bl', 'Hr') THEN '0 th'
                     WHEN rp.umurdaftar <= 5 THEN '1-5 th' WHEN rp.umurdaftar <= 14 THEN '6-14 th'
                     WHEN rp.umurdaftar <= 24 THEN '15-24 th' WHEN rp.umurdaftar <= 44 THEN '25-44 th'
                     WHEN rp.umurdaftar <= 64 THEN '45-64 th' ELSE '65+ th' END AS kelompok_umur,
                SUM(ps.jk = 'L') AS laki_laki, SUM(ps.jk = 'P') AS perempuan, COUNT(*) AS total
            FROM reg_periksa rp JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
            WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN '{$this->from}' AND '{$this->to}'
            GROUP BY kelompok_umur
            ORDER BY FIELD(kelompok_umur, '0 th', '1-5 th', '6-14 th', '15-24 th', '25-44 th', '45-64 th', '65+ th')");
    }

    /** Daftar laporan yang bisa diunduh sebagai CSV. */
    public static function exports(): array
    {
        return [
            'tren' => ['Tren kunjungan harian', 'trend'], 'poli' => ['Kunjungan per poliklinik', 'byClinic'],
            'penjab' => ['Kunjungan per cara bayar', 'byPayer'], 'dokter' => ['Kinerja dokter rawat jalan', 'doctors'],
            'antrol' => ['Antrean online per poli', 'antrolClinic'], 'bangsal' => ['Okupansi per bangsal', 'wards'],
            'status-pulang' => ['Status pulang rawat inap', 'dischargeStatus'], 'pembayaran' => ['Pembayaran', 'payments'],
            'piutang' => ['Piutang per penjamin', 'receivables'], 'billing' => ['Komposisi tagihan', 'billingCategories'],
            'obat' => ['10 obat terbanyak', 'topDrugs'], 'diagnosa' => ['10 diagnosa terbanyak', 'topDiagnoses'],
            'wilayah' => ['Asal pasien per kecamatan', 'regions'], 'demografi' => ['Demografi pasien', 'demography'],
        ];
    }
}
