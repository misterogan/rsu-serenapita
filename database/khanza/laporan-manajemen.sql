-- ================================================================
-- Query laporan manajemen dari database SIMRS Khanza (struktur bawaan)
-- Dipakai oleh menu Dasbor manajemen di backoffice website RSU Serenapita.
-- Jalankan di HeidiSQL / Navicat / phpMyAdmin pada database Khanza (biasanya bernama sik).
-- Atur periode di bawah, lalu jalankan query yang dibutuhkan.
-- Semua query hanya membaca (SELECT), tidak mengubah data.
-- ================================================================

SET @mulai = '2026-10-01';
SET @selesai = '2026-10-31';
SET @poli_igd = 'IGDK';   -- kode poli IGD di tabel poliklinik

-- ----------------------------------------------------------------
-- 1. Ringkasan kunjungan
-- ----------------------------------------------------------------
SELECT
      SUM(rp.stts <> 'Batal') AS kunjungan,
      SUM(rp.stts = 'Batal') AS batal,
      SUM(rp.stts <> 'Batal' AND rp.status_lanjut = 'Ralan' AND rp.kd_poli <> @poli_igd) AS rawat_jalan,
      SUM(rp.stts <> 'Batal' AND rp.kd_poli = @poli_igd) AS igd,
      SUM(rp.stts <> 'Batal' AND rp.status_lanjut = 'Ranap') AS lanjut_ranap,
      SUM(rp.stts <> 'Batal' AND rp.stts_daftar = 'Baru') AS pasien_baru,
      SUM(rp.stts <> 'Batal' AND pj.png_jawab LIKE '%BPJS%') AS bpjs,
      COUNT(DISTINCT CASE WHEN rp.stts <> 'Batal' THEN rp.no_rkm_medis END) AS pasien_unik
  FROM reg_periksa rp
  LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
  WHERE rp.tgl_registrasi BETWEEN @mulai AND @selesai;

-- ----------------------------------------------------------------
-- 2. Tren kunjungan harian
-- ----------------------------------------------------------------
SELECT rp.tgl_registrasi AS tanggal,
      SUM(rp.status_lanjut = 'Ralan' AND rp.kd_poli <> @poli_igd) AS rawat_jalan,
      SUM(rp.kd_poli = @poli_igd) AS igd,
      SUM(rp.status_lanjut = 'Ranap') AS lanjut_ranap,
      COUNT(*) AS total
  FROM reg_periksa rp
  WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY rp.tgl_registrasi ORDER BY rp.tgl_registrasi;

-- ----------------------------------------------------------------
-- 3. Kunjungan per poliklinik
-- ----------------------------------------------------------------
SELECT pl.nm_poli AS poliklinik,
      SUM(rp.stts <> 'Batal') AS kunjungan,
      SUM(rp.stts <> 'Batal' AND rp.stts_daftar = 'Baru') AS baru,
      SUM(rp.stts <> 'Batal' AND rp.stts_daftar = 'Lama') AS lama,
      SUM(rp.stts <> 'Batal' AND pj.png_jawab LIKE '%BPJS%') AS bpjs,
      SUM(rp.stts = 'Batal') AS batal
  FROM reg_periksa rp
  JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
  LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
  WHERE rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY rp.kd_poli, pl.nm_poli ORDER BY kunjungan DESC;

-- ----------------------------------------------------------------
-- 4. Kunjungan per cara bayar
-- ----------------------------------------------------------------
SELECT COALESCE(pj.png_jawab, rp.kd_pj) AS cara_bayar,
      COUNT(*) AS kunjungan,
      SUM(rp.status_lanjut = 'Ranap') AS lanjut_ranap
  FROM reg_periksa rp
  LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
  WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY rp.kd_pj, pj.png_jawab ORDER BY kunjungan DESC;

-- ----------------------------------------------------------------
-- 5. Kinerja dokter rawat jalan (waktu tunggu dan lama layanan)
-- ----------------------------------------------------------------
SELECT d.nm_dokter AS dokter,
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
             WHERE tgl_perawatan BETWEEN @mulai AND @selesai GROUP BY no_rawat) pr ON pr.no_rawat = rp.no_rawat
  LEFT JOIN (SELECT t4.no_rawat, TIMESTAMPDIFF(MINUTE, t4.waktu, t5.waktu) AS layan
             FROM referensi_mobilejkn_bpjs_taskid t4
             JOIN referensi_mobilejkn_bpjs_taskid t5 ON t5.no_rawat = t4.no_rawat AND t5.taskid = '5'
             WHERE t4.taskid = '4' AND t4.waktu BETWEEN @mulai AND CONCAT(@selesai, ' 23:59:59')) tk ON tk.no_rawat = rp.no_rawat
  WHERE rp.stts <> 'Batal' AND rp.status_lanjut = 'Ralan' AND rp.kd_poli <> @poli_igd
    AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY rp.kd_dokter, d.nm_dokter ORDER BY pasien DESC;

-- ----------------------------------------------------------------
-- 6. Antrean online: jumlah booking per status
-- ----------------------------------------------------------------
SELECT m.status, COUNT(*) AS jumlah
  FROM referensi_mobilejkn_bpjs m
  WHERE m.tanggalperiksa BETWEEN @mulai AND @selesai
  GROUP BY m.status ORDER BY jumlah DESC;

-- ----------------------------------------------------------------
-- 7. Antrean online per poli
-- ----------------------------------------------------------------
SELECT COALESCE(mp.nm, m.kodepoli) AS poli,
      COUNT(*) AS booking,
      SUM(m.status = 'Checkin') AS checkin,
      SUM(m.status = 'Batal') AS batal,
      SUM(m.status = 'Belum') AS belum_datang,
      SUM(m.pasienbaru = '1') AS pasien_baru
  FROM referensi_mobilejkn_bpjs m
  LEFT JOIN (SELECT kd_poli_bpjs, MAX(nm_poli_bpjs) AS nm FROM maping_poli_bpjs GROUP BY kd_poli_bpjs) mp ON mp.kd_poli_bpjs = m.kodepoli
  WHERE m.tanggalperiksa BETWEEN @mulai AND @selesai
  GROUP BY m.kodepoli, mp.nm ORDER BY booking DESC;

-- ----------------------------------------------------------------
-- 8. Antrean online: rata-rata waktu per tahap (task ID)
-- ----------------------------------------------------------------
SELECT COUNT(*) AS pasien,
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
        WHERE waktu BETWEEN @mulai AND CONCAT(@selesai, ' 23:59:59')
        GROUP BY no_rawat) x;

-- ----------------------------------------------------------------
-- 9. Pasien dirawat dan tempat tidur saat ini
-- ----------------------------------------------------------------
SELECT
      (SELECT COUNT(DISTINCT no_rawat) FROM kamar_inap WHERE stts_pulang = '-') AS dirawat,
      (SELECT COUNT(*) FROM kamar WHERE statusdata = '1') AS tempat_tidur,
      (SELECT COUNT(*) FROM kamar WHERE statusdata = '1' AND status = 'ISI') AS terisi;

-- ----------------------------------------------------------------
-- 10. Okupansi tempat tidur per bangsal
-- ----------------------------------------------------------------
SELECT b.nm_bangsal AS bangsal, k.kelas,
      COUNT(*) AS tempat_tidur, SUM(k.status = 'ISI') AS terisi, SUM(k.status = 'KOSONG') AS kosong,
      SUM(k.status IN ('DIBERSIHKAN', 'DIBOOKING')) AS disiapkan
  FROM kamar k JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
  WHERE k.statusdata = '1'
  GROUP BY b.kd_bangsal, b.nm_bangsal, k.kelas ORDER BY b.nm_bangsal, k.kelas;

-- ----------------------------------------------------------------
-- 11. Rawat inap: jumlah hari perawatan (untuk BOR dan TOI)
-- ----------------------------------------------------------------
SELECT SUM(
          CASE WHEN stts_pulang = 'Pindah Kamar' THEN GREATEST(0, DATEDIFF(LEAST(tgl_keluar, DATE_ADD(@selesai, INTERVAL 1 DAY)), GREATEST(tgl_masuk, @mulai)))
               ELSE GREATEST(1, DATEDIFF(LEAST(CASE WHEN stts_pulang = '-' OR tgl_keluar IS NULL OR tgl_keluar < tgl_masuk THEN DATE_ADD(@selesai, INTERVAL 1 DAY) ELSE tgl_keluar END, DATE_ADD(@selesai, INTERVAL 1 DAY)), GREATEST(tgl_masuk, @mulai))) END
      ) AS hari_perawatan
  FROM kamar_inap
  WHERE tgl_masuk <= @selesai AND (stts_pulang = '-' OR tgl_keluar >= @mulai);

-- ----------------------------------------------------------------
-- 12. Rawat inap: pasien keluar, lama dirawat, kematian (untuk ALOS, BTO, GDR, NDR)
-- ----------------------------------------------------------------
SELECT COUNT(*) AS keluar, SUM(x.lama_total) AS lama_total,
      SUM(x.meninggal) AS meninggal, SUM(x.meninggal = 1 AND x.lama_total >= 2) AS meninggal_48
  FROM (SELECT ki.no_rawat, SUM(ki.lama) AS lama_total, MAX(d.meninggal) AS meninggal
        FROM kamar_inap ki
        JOIN (SELECT no_rawat, MAX(stts_pulang = 'Meninggal') AS meninggal FROM kamar_inap
              WHERE tgl_keluar BETWEEN @mulai AND @selesai AND stts_pulang NOT IN ('-', 'Pindah Kamar')
              GROUP BY no_rawat) d ON d.no_rawat = ki.no_rawat
        GROUP BY ki.no_rawat) x;

-- ----------------------------------------------------------------
-- 13. Rawat inap: pasien masuk
-- ----------------------------------------------------------------
SELECT COUNT(DISTINCT no_rawat) AS masuk FROM kamar_inap WHERE tgl_masuk BETWEEN @mulai AND @selesai;

-- ----------------------------------------------------------------
-- 14. Status pulang rawat inap
-- ----------------------------------------------------------------
SELECT stts_pulang AS status_pulang, COUNT(DISTINCT no_rawat) AS pasien
  FROM kamar_inap
  WHERE tgl_keluar BETWEEN @mulai AND @selesai AND stts_pulang NOT IN ('-', 'Pindah Kamar')
  GROUP BY stts_pulang ORDER BY pasien DESC;

-- ----------------------------------------------------------------
-- 15. Pembayaran per cara bayar
-- ----------------------------------------------------------------
SELECT jenis, nama_bayar AS cara_bayar, COUNT(*) AS nota, SUM(besar_bayar) AS total FROM (
      SELECT 'Rawat jalan' AS jenis, d.nama_bayar, n.no_rawat, d.besar_bayar
      FROM nota_jalan n JOIN detail_nota_jalan d ON d.no_rawat = n.no_rawat
      WHERE n.tanggal BETWEEN @mulai AND @selesai
      UNION ALL
      SELECT 'Rawat inap', d.nama_bayar, n.no_rawat, d.besar_bayar
      FROM nota_inap n JOIN detail_nota_inap d ON d.no_rawat = n.no_rawat
      WHERE n.tanggal BETWEEN @mulai AND @selesai
  ) p GROUP BY jenis, nama_bayar ORDER BY total DESC;

-- ----------------------------------------------------------------
-- 16. Piutang per penjamin
-- ----------------------------------------------------------------
SELECT COALESCE(pj.png_jawab, rp.kd_pj) AS penjamin,
      COUNT(*) AS pasien, SUM(p.totalpiutang) AS total_piutang, SUM(p.sisapiutang) AS sisa_piutang
  FROM piutang_pasien p
  JOIN reg_periksa rp ON rp.no_rawat = p.no_rawat
  LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
  WHERE p.tgl_piutang BETWEEN @mulai AND @selesai
  GROUP BY rp.kd_pj, pj.png_jawab ORDER BY total_piutang DESC;

-- ----------------------------------------------------------------
-- 17. Komposisi tagihan per kategori
-- ----------------------------------------------------------------
SELECT b.status AS kategori, COUNT(DISTINCT b.no_rawat) AS pasien, SUM(b.totalbiaya) AS total
  FROM billing b
  WHERE b.tgl_byr BETWEEN @mulai AND @selesai
    AND b.status NOT IN ('-', 'Tagihan') AND b.status NOT LIKE 'Ttl%'
  GROUP BY b.status ORDER BY total DESC;

-- ----------------------------------------------------------------
-- 18. Pelayanan penunjang
-- ----------------------------------------------------------------
SELECT
      (SELECT COUNT(DISTINCT no_rawat) FROM periksa_lab WHERE tgl_periksa BETWEEN @mulai AND @selesai) AS lab_pasien,
      (SELECT COALESCE(SUM(biaya), 0) FROM periksa_lab WHERE tgl_periksa BETWEEN @mulai AND @selesai) AS lab_nilai,
      (SELECT COUNT(DISTINCT no_rawat) FROM periksa_radiologi WHERE tgl_periksa BETWEEN @mulai AND @selesai) AS rad_pasien,
      (SELECT COALESCE(SUM(biaya), 0) FROM periksa_radiologi WHERE tgl_periksa BETWEEN @mulai AND @selesai) AS rad_nilai,
      (SELECT COUNT(*) FROM operasi WHERE tgl_operasi BETWEEN @mulai AND CONCAT(@selesai, ' 23:59:59')) AS operasi,
      (SELECT COUNT(*) FROM resep_obat WHERE tgl_perawatan BETWEEN @mulai AND @selesai) AS resep,
      (SELECT COUNT(*) FROM rujuk WHERE tgl_rujuk BETWEEN @mulai AND @selesai) AS rujukan_keluar;

-- ----------------------------------------------------------------
-- 19. 10 obat dengan nilai terbesar
-- ----------------------------------------------------------------
SELECT b.nama_brng AS obat, SUM(d.jml) AS jumlah, SUM(d.total) AS nilai
  FROM detail_pemberian_obat d JOIN databarang b ON b.kode_brng = d.kode_brng
  WHERE d.tgl_perawatan BETWEEN @mulai AND @selesai
  GROUP BY d.kode_brng, b.nama_brng ORDER BY nilai DESC LIMIT 10;

-- ----------------------------------------------------------------
-- 20. 10 diagnosa utama terbanyak
-- ----------------------------------------------------------------
SELECT p.kd_penyakit AS kode, p.nm_penyakit AS diagnosa,
      SUM(dp.status = 'Ralan') AS rawat_jalan, SUM(dp.status = 'Ranap') AS rawat_inap, COUNT(*) AS total
  FROM diagnosa_pasien dp
  JOIN reg_periksa rp ON rp.no_rawat = dp.no_rawat
  JOIN penyakit p ON p.kd_penyakit = dp.kd_penyakit
  WHERE dp.prioritas = 1 AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY dp.kd_penyakit, p.nm_penyakit ORDER BY total DESC LIMIT 10;

-- ----------------------------------------------------------------
-- 21. Asal pasien per kecamatan
-- ----------------------------------------------------------------
SELECT COALESCE(k.nm_kec, '-') AS kecamatan, COUNT(*) AS kunjungan
  FROM reg_periksa rp
  JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
  LEFT JOIN kecamatan k ON k.kd_kec = ps.kd_kec
  WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY ps.kd_kec, k.nm_kec ORDER BY kunjungan DESC LIMIT 10;

-- ----------------------------------------------------------------
-- 22. Kunjungan per umur dan jenis kelamin
-- ----------------------------------------------------------------
SELECT
      CASE WHEN rp.sttsumur IN ('Bl', 'Hr') THEN '0 th'
           WHEN rp.umurdaftar <= 5 THEN '1-5 th' WHEN rp.umurdaftar <= 14 THEN '6-14 th'
           WHEN rp.umurdaftar <= 24 THEN '15-24 th' WHEN rp.umurdaftar <= 44 THEN '25-44 th'
           WHEN rp.umurdaftar <= 64 THEN '45-64 th' ELSE '65+ th' END AS kelompok_umur,
      SUM(ps.jk = 'L') AS laki_laki, SUM(ps.jk = 'P') AS perempuan, COUNT(*) AS total
  FROM reg_periksa rp JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
  WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY kelompok_umur
  ORDER BY FIELD(kelompok_umur, '0 th', '1-5 th', '6-14 th', '15-24 th', '25-44 th', '45-64 th', '65+ th');

-- Rumus indikator rawat inap (t = jumlah hari periode, TT = jumlah tempat tidur aktif):
--   BOR  = hari_perawatan / (TT x t) x 100%        ideal 60-85%
--   ALOS = lama_total / keluar                     ideal 6-9 hari
--   TOI  = (TT x t - hari_perawatan) / keluar      ideal 1-3 hari
--   BTO  = keluar / TT                             ideal 40-50 kali per tahun
--   GDR  = meninggal / keluar x 1000               ideal <= 45 per mil
--   NDR  = meninggal_48 / keluar x 1000            ideal <= 25 per mil
