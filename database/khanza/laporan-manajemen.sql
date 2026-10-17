-- ================================================================
-- Query laporan Dasbor manajemen dari database SIMRS Khanza
-- Sudah dicocokkan dengan struktur tabel Khanza RSU Serenapita.
-- Jalankan di HeidiSQL pada database sik. Atur periode di bawah.
-- Semua query hanya membaca (SELECT).
-- ================================================================

SET @mulai = '2026-10-01';
SET @selesai = '2026-10-31';
SET @poli_igd = 'IGDK';

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
SELECT rp.kd_poli AS kode, pl.nm_poli AS poliklinik,
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
-- 5. Kinerja dokter rawat jalan
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
-- 6. Ketepatan jam mulai praktik dokter
-- ----------------------------------------------------------------
SELECT d.nm_dokter AS dokter, COUNT(*) AS hari_praktik,
      ROUND(AVG(y.selisih)) AS rata_selisih_menit, SUM(y.selisih <= 15) AS tepat_waktu
  FROM (SELECT m.kd_dokter, TIMESTAMPDIFF(MINUTE, CONCAT(m.tgl, ' ', j.jam_mulai), CONCAT(m.tgl, ' ', m.mulai)) AS selisih
        FROM (SELECT rp.kd_dokter, rp.tgl_registrasi AS tgl, MIN(pr.jam_rawat) AS mulai
              FROM reg_periksa rp
              JOIN pemeriksaan_ralan pr ON pr.no_rawat = rp.no_rawat AND pr.tgl_perawatan = rp.tgl_registrasi
              WHERE rp.stts <> 'Batal' AND rp.status_lanjut = 'Ralan' AND rp.kd_poli <> @poli_igd
                AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
              GROUP BY rp.kd_dokter, rp.tgl_registrasi) m
        JOIN (SELECT kd_dokter, hari_kerja, MIN(jam_mulai) AS jam_mulai FROM jadwal GROUP BY kd_dokter, hari_kerja) j
          ON j.kd_dokter = m.kd_dokter AND j.hari_kerja = ELT(DAYOFWEEK(m.tgl), 'AKHAD', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU')) y
  JOIN dokter d ON d.kd_dokter = y.kd_dokter
  WHERE y.selisih BETWEEN -120 AND 480
  GROUP BY y.kd_dokter, d.nm_dokter ORDER BY rata_selisih_menit DESC;

-- ----------------------------------------------------------------
-- 7. Jam sibuk pendaftaran
-- ----------------------------------------------------------------
SELECT DAYOFWEEK(rp.tgl_registrasi) AS hari, HOUR(rp.jam_reg) AS jam,
      SUM(rp.kd_poli <> @poli_igd) AS rawat_jalan, SUM(rp.kd_poli = @poli_igd) AS igd, COUNT(*) AS total
  FROM reg_periksa rp
  WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY DAYOFWEEK(rp.tgl_registrasi), HOUR(rp.jam_reg);

-- ----------------------------------------------------------------
-- 8. Kepatuhan waktu tunggu rawat jalan <= 60 menit
-- ----------------------------------------------------------------
SELECT COUNT(*) AS pasien, SUM(x.menit <= 60) AS tepat, ROUND(AVG(x.menit)) AS rata_menit
  FROM (SELECT TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, ' ', rp.jam_reg), MIN(CONCAT(pr.tgl_perawatan, ' ', pr.jam_rawat))) AS menit
        FROM reg_periksa rp
        JOIN pemeriksaan_ralan pr ON pr.no_rawat = rp.no_rawat
        WHERE rp.stts <> 'Batal' AND rp.status_lanjut = 'Ralan' AND rp.kd_poli <> @poli_igd
          AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
        GROUP BY rp.no_rawat, rp.tgl_registrasi, rp.jam_reg) x
  WHERE x.menit BETWEEN 0 AND 600;

-- ----------------------------------------------------------------
-- 9. Antrean online per status
-- ----------------------------------------------------------------
SELECT m.status, COUNT(*) AS jumlah
  FROM referensi_mobilejkn_bpjs m
  WHERE m.tanggalperiksa BETWEEN @mulai AND @selesai
  GROUP BY m.status ORDER BY jumlah DESC;

-- ----------------------------------------------------------------
-- 10. Antrean online per poli
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
-- 11. Antrean online: waktu per tahap
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
-- 12. 10 diagnosa terbanyak
-- ----------------------------------------------------------------
SELECT p.kd_penyakit AS kode, p.nm_penyakit AS diagnosa,
      SUM(dp.status = 'Ralan') AS rawat_jalan, SUM(dp.status = 'Ranap') AS rawat_inap, COUNT(*) AS total
  FROM diagnosa_pasien dp
  JOIN reg_periksa rp ON rp.no_rawat = dp.no_rawat
  JOIN penyakit p ON p.kd_penyakit = dp.kd_penyakit
  WHERE dp.prioritas = 1 AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY dp.kd_penyakit, p.nm_penyakit ORDER BY total DESC LIMIT 10;

-- ----------------------------------------------------------------
-- 13. Asal pasien per kecamatan
-- ----------------------------------------------------------------
SELECT COALESCE(k.nm_kec, '-') AS kecamatan, COUNT(*) AS kunjungan
  FROM reg_periksa rp
  JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
  LEFT JOIN kecamatan k ON k.kd_kec = ps.kd_kec
  WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY ps.kd_kec, k.nm_kec ORDER BY kunjungan DESC LIMIT 10;

-- ----------------------------------------------------------------
-- 14. Umur dan jenis kelamin
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

-- ----------------------------------------------------------------
-- 15. Rujukan masuk
-- ----------------------------------------------------------------
SELECT rm.perujuk, COUNT(*) AS pasien, SUM(rp.status_lanjut = 'Ranap') AS dirawat
  FROM rujuk_masuk rm JOIN reg_periksa rp ON rp.no_rawat = rm.no_rawat
  WHERE rp.tgl_registrasi BETWEEN @mulai AND @selesai
  GROUP BY rm.perujuk ORDER BY pasien DESC LIMIT 10;

-- ----------------------------------------------------------------
-- 16. Tren kunjungan 12 bulan
-- ----------------------------------------------------------------
SELECT DATE_FORMAT(rp.tgl_registrasi, '%Y-%m') AS bulan, COUNT(*) AS kunjungan,
      SUM(rp.stts_daftar = 'Baru') AS pasien_baru, SUM(rp.kd_poli = @poli_igd) AS igd, SUM(rp.status_lanjut = 'Ranap') AS lanjut_ranap
  FROM reg_periksa rp
  WHERE rp.stts <> 'Batal' AND rp.tgl_registrasi BETWEEN DATE_SUB(DATE_FORMAT(@selesai, '%Y-%m-01'), INTERVAL 11 MONTH) AND @selesai
  GROUP BY DATE_FORMAT(rp.tgl_registrasi, '%Y-%m') ORDER BY bulan;

-- ----------------------------------------------------------------
-- 17. Pasien dirawat dan tempat tidur saat ini
-- ----------------------------------------------------------------
SELECT
      (SELECT COUNT(DISTINCT no_rawat) FROM kamar_inap WHERE stts_pulang = '-') AS dirawat,
      (SELECT COUNT(*) FROM kamar WHERE statusdata = '1') AS tempat_tidur,
      (SELECT COUNT(*) FROM kamar WHERE statusdata = '1' AND status = 'ISI') AS terisi;

-- ----------------------------------------------------------------
-- 18. Okupansi per bangsal
-- ----------------------------------------------------------------
SELECT b.nm_bangsal AS bangsal, k.kelas,
      COUNT(*) AS tempat_tidur, SUM(k.status = 'ISI') AS terisi, SUM(k.status = 'KOSONG') AS kosong,
      SUM(k.status IN ('DIBERSIHKAN', 'DIBOOKING')) AS disiapkan
  FROM kamar k JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
  WHERE k.statusdata = '1'
  GROUP BY b.kd_bangsal, b.nm_bangsal, k.kelas ORDER BY b.nm_bangsal, k.kelas;

-- ----------------------------------------------------------------
-- 19. Indikator rawat inap (BOR, ALOS, TOI, BTO, GDR, NDR) (ranap_hp)
-- ----------------------------------------------------------------
SELECT SUM(
          CASE WHEN stts_pulang = 'Pindah Kamar' THEN GREATEST(0, DATEDIFF(LEAST(tgl_keluar, DATE_ADD(@selesai, INTERVAL 1 DAY)), GREATEST(tgl_masuk, @mulai)))
               ELSE GREATEST(1, DATEDIFF(LEAST(CASE WHEN stts_pulang = '-' OR tgl_keluar IS NULL OR tgl_keluar < tgl_masuk THEN DATE_ADD(@selesai, INTERVAL 1 DAY) ELSE tgl_keluar END, DATE_ADD(@selesai, INTERVAL 1 DAY)), GREATEST(tgl_masuk, @mulai))) END
      ) AS hari_perawatan
  FROM kamar_inap
  WHERE tgl_masuk <= @selesai AND (stts_pulang = '-' OR tgl_keluar >= @mulai);

-- ----------------------------------------------------------------
-- 20. Indikator rawat inap (BOR, ALOS, TOI, BTO, GDR, NDR) (ranap_keluar)
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
-- 21. Indikator rawat inap (BOR, ALOS, TOI, BTO, GDR, NDR) (ranap_masuk)
-- ----------------------------------------------------------------
SELECT COUNT(DISTINCT no_rawat) AS masuk FROM kamar_inap WHERE tgl_masuk BETWEEN @mulai AND @selesai;

-- ----------------------------------------------------------------
-- 22. Indikator rawat inap (BOR, ALOS, TOI, BTO, GDR, NDR) (ranap_kini)
-- ----------------------------------------------------------------
SELECT
      (SELECT COUNT(DISTINCT no_rawat) FROM kamar_inap WHERE stts_pulang = '-') AS dirawat,
      (SELECT COUNT(*) FROM kamar WHERE statusdata = '1') AS tempat_tidur,
      (SELECT COUNT(*) FROM kamar WHERE statusdata = '1' AND status = 'ISI') AS terisi;

-- ----------------------------------------------------------------
-- 23. Status pulang
-- ----------------------------------------------------------------
SELECT stts_pulang AS status_pulang, COUNT(DISTINCT no_rawat) AS pasien
  FROM kamar_inap
  WHERE tgl_keluar BETWEEN @mulai AND @selesai AND stts_pulang NOT IN ('-', 'Pindah Kamar')
  GROUP BY stts_pulang ORDER BY pasien DESC;

-- ----------------------------------------------------------------
-- 24. Rawat inap per DPJP
-- ----------------------------------------------------------------
SELECT d.nm_dokter AS dokter, COUNT(*) AS pasien_pulang, ROUND(AVG(x.lama), 1) AS rata_lama_hari,
      SUM(x.meninggal) AS meninggal, SUM(x.aps) AS pulang_paksa_aps
  FROM (SELECT ki.no_rawat, SUM(ki.lama) AS lama, MAX(ki.stts_pulang = 'Meninggal') AS meninggal,
               MAX(ki.stts_pulang IN ('APS', 'Atas Permintaan Sendiri', 'Pulang Paksa')) AS aps
        FROM kamar_inap ki
        WHERE ki.no_rawat IN (SELECT no_rawat FROM kamar_inap WHERE tgl_keluar BETWEEN @mulai AND @selesai AND stts_pulang NOT IN ('-', 'Pindah Kamar'))
        GROUP BY ki.no_rawat) x
  JOIN dpjp_ranap dp ON dp.no_rawat = x.no_rawat
  JOIN dokter d ON d.kd_dokter = dp.kd_dokter
  GROUP BY dp.kd_dokter, d.nm_dokter ORDER BY pasien_pulang DESC;

-- ----------------------------------------------------------------
-- 25. Dirawat ulang <= 30 hari
-- ----------------------------------------------------------------
SELECT COUNT(*) AS pasien_pulang, SUM(x.ulang) AS dirawat_ulang FROM (
      SELECT e.no_rawat, MAX(CASE WHEN n.no_rawat IS NULL THEN 0 ELSE 1 END) AS ulang
      FROM (SELECT ki.no_rawat, rp.no_rkm_medis, MAX(ki.tgl_keluar) AS keluar
            FROM kamar_inap ki JOIN reg_periksa rp ON rp.no_rawat = ki.no_rawat
            WHERE ki.tgl_keluar BETWEEN @mulai AND @selesai AND ki.stts_pulang NOT IN ('-', 'Pindah Kamar', 'Meninggal')
            GROUP BY ki.no_rawat, rp.no_rkm_medis) e
      LEFT JOIN (SELECT ki.no_rawat, rp.no_rkm_medis, MIN(ki.tgl_masuk) AS masuk
                 FROM kamar_inap ki JOIN reg_periksa rp ON rp.no_rawat = ki.no_rawat
                 WHERE ki.tgl_masuk BETWEEN @mulai AND DATE_ADD(@selesai, INTERVAL 30 DAY)
                 GROUP BY ki.no_rawat, rp.no_rkm_medis) n
        ON n.no_rkm_medis = e.no_rkm_medis AND n.no_rawat <> e.no_rawat AND n.masuk >= e.keluar AND DATEDIFF(n.masuk, e.keluar) <= 30
      GROUP BY e.no_rawat) x;

-- ----------------------------------------------------------------
-- 26. Ringkasan penunjang
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
-- 27. Lama hasil lab dan radiologi
-- ----------------------------------------------------------------
SELECT penunjang, layanan, COUNT(*) AS pemeriksaan, ROUND(AVG(menit)) AS rata_menit,
      SUM(menit <= 60) AS sd_60_menit, SUM(menit > 60 AND menit <= 120) AS sd_120_menit,
      SUM(menit > 120 AND menit <= 240) AS sd_240_menit, SUM(menit > 240) AS lebih_240_menit
  FROM (SELECT 'Laboratorium' AS penunjang, p.status AS layanan,
      TIMESTAMPDIFF(MINUTE, CONCAT(p.tgl_permintaan, ' ', p.jam_permintaan), CONCAT(p.tgl_hasil, ' ', p.jam_hasil)) AS menit
    FROM permintaan_lab p
    WHERE p.tgl_permintaan BETWEEN @mulai AND @selesai AND p.tgl_hasil > '0000-00-00' UNION ALL SELECT 'Radiologi' AS penunjang, p.status AS layanan,
      TIMESTAMPDIFF(MINUTE, CONCAT(p.tgl_permintaan, ' ', p.jam_permintaan), CONCAT(p.tgl_hasil, ' ', p.jam_hasil)) AS menit
    FROM permintaan_radiologi p
    WHERE p.tgl_permintaan BETWEEN @mulai AND @selesai AND p.tgl_hasil > '0000-00-00') x
  WHERE menit BETWEEN 0 AND 4320
  GROUP BY penunjang, layanan ORDER BY penunjang, layanan;

-- ----------------------------------------------------------------
-- 28. Waktu tunggu resep
-- ----------------------------------------------------------------
SELECT x.jenis, COUNT(*) AS resep, ROUND(AVG(x.menit)) AS rata_menit,
      SUM(x.menit <= x.standar) AS sesuai_standar, MAX(x.standar) AS standar_menit
  FROM (SELECT CASE WHEN EXISTS (SELECT 1 FROM resep_dokter_racikan rk WHERE rk.no_resep = ro.no_resep) THEN 'Racikan' ELSE 'Non-racikan' END AS jenis,
               CASE WHEN EXISTS (SELECT 1 FROM resep_dokter_racikan rk WHERE rk.no_resep = ro.no_resep) THEN 60 ELSE 30 END AS standar,
               TIMESTAMPDIFF(MINUTE, CONCAT(ro.tgl_peresepan, ' ', ro.jam_peresepan), CONCAT(ro.tgl_penyerahan, ' ', ro.jam_penyerahan)) AS menit
        FROM resep_obat ro
        WHERE ro.status = 'ralan' AND ro.tgl_peresepan BETWEEN @mulai AND @selesai
          AND ro.tgl_penyerahan > '0000-00-00') x
  WHERE x.menit BETWEEN 0 AND 600
  GROUP BY x.jenis ORDER BY x.jenis;

-- ----------------------------------------------------------------
-- 29. Laboratorium per kategori
-- ----------------------------------------------------------------
SELECT pl.kategori, COUNT(DISTINCT pl.no_rawat) AS pasien, COUNT(*) AS pemeriksaan
  FROM periksa_lab pl WHERE pl.tgl_periksa BETWEEN @mulai AND @selesai GROUP BY pl.kategori ORDER BY pemeriksaan DESC;

-- ----------------------------------------------------------------
-- 30. Operasi per kategori dan operator (operasi_kategori)
-- ----------------------------------------------------------------
SELECT o.kategori, COUNT(*) AS operasi FROM operasi o
  WHERE o.tgl_operasi BETWEEN @mulai AND CONCAT(@selesai, ' 23:59:59') GROUP BY o.kategori ORDER BY operasi DESC;

-- ----------------------------------------------------------------
-- 31. Operasi per kategori dan operator (operasi_operator)
-- ----------------------------------------------------------------
SELECT d.nm_dokter AS operator, COUNT(*) AS operasi FROM operasi o
  JOIN dokter d ON d.kd_dokter = o.operator1
  WHERE o.tgl_operasi BETWEEN @mulai AND CONCAT(@selesai, ' 23:59:59') GROUP BY o.operator1, d.nm_dokter ORDER BY operasi DESC LIMIT 10;

-- ----------------------------------------------------------------
-- 32. 10 obat terbanyak
-- ----------------------------------------------------------------
SELECT b.nama_brng AS obat, SUM(d.jml) AS jumlah, SUM(d.total) AS nilai
  FROM detail_pemberian_obat d JOIN databarang b ON b.kode_brng = d.kode_brng
  WHERE d.tgl_perawatan BETWEEN @mulai AND @selesai
  GROUP BY d.kode_brng, b.nama_brng ORDER BY nilai DESC LIMIT 10;

-- ----------------------------------------------------------------
-- 33. Obat di bawah stok minimal
-- ----------------------------------------------------------------
SELECT b.kode_brng AS kode, b.nama_brng AS obat, b.stokminimal AS stok_minimal, COALESCE(SUM(g.stok), 0) AS stok
  FROM databarang b LEFT JOIN gudangbarang g ON g.kode_brng = b.kode_brng
  WHERE b.status = '1' AND b.stokminimal > 0
  GROUP BY b.kode_brng, b.nama_brng, b.stokminimal
  HAVING stok < b.stokminimal
  ORDER BY stok / b.stokminimal, b.nama_brng LIMIT 50;

-- ----------------------------------------------------------------
-- 34. Obat kedaluwarsa <= 90 hari
-- ----------------------------------------------------------------
SELECT b.nama_brng AS obat, db.no_batch, db.tgl_kadaluarsa AS kedaluwarsa,
      DATEDIFF(db.tgl_kadaluarsa, CURDATE()) AS sisa_hari, db.sisa
  FROM data_batch db JOIN databarang b ON b.kode_brng = db.kode_brng
  WHERE db.sisa > 0 AND db.tgl_kadaluarsa > '0000-00-00' AND db.tgl_kadaluarsa <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
  ORDER BY db.tgl_kadaluarsa LIMIT 50;

-- ----------------------------------------------------------------
-- 35. Pembayaran per cara bayar
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
-- 36. Piutang baru per penjamin
-- ----------------------------------------------------------------
SELECT COALESCE(pj.png_jawab, rp.kd_pj) AS penjamin,
      COUNT(*) AS pasien, SUM(p.totalpiutang) AS total_piutang, SUM(p.sisapiutang) AS sisa_piutang
  FROM piutang_pasien p
  JOIN reg_periksa rp ON rp.no_rawat = p.no_rawat
  LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
  WHERE p.tgl_piutang BETWEEN @mulai AND @selesai
  GROUP BY rp.kd_pj, pj.png_jawab ORDER BY total_piutang DESC;

-- ----------------------------------------------------------------
-- 37. Umur piutang
-- ----------------------------------------------------------------
SELECT COALESCE(pj.png_jawab, rp.kd_pj) AS penjamin, COUNT(*) AS pasien,
      SUM(CASE WHEN DATEDIFF(CURDATE(), p.tgl_piutang) <= 30 THEN p.sisapiutang ELSE 0 END) AS hari_0_30,
      SUM(CASE WHEN DATEDIFF(CURDATE(), p.tgl_piutang) BETWEEN 31 AND 60 THEN p.sisapiutang ELSE 0 END) AS hari_31_60,
      SUM(CASE WHEN DATEDIFF(CURDATE(), p.tgl_piutang) BETWEEN 61 AND 90 THEN p.sisapiutang ELSE 0 END) AS hari_61_90,
      SUM(CASE WHEN DATEDIFF(CURDATE(), p.tgl_piutang) > 90 THEN p.sisapiutang ELSE 0 END) AS lebih_90,
      SUM(p.sisapiutang) AS total_sisa
  FROM piutang_pasien p
  JOIN reg_periksa rp ON rp.no_rawat = p.no_rawat
  LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
  WHERE p.sisapiutang > 0
  GROUP BY rp.kd_pj, pj.png_jawab ORDER BY total_sisa DESC;

-- ----------------------------------------------------------------
-- 38. Pelunasan piutang
-- ----------------------------------------------------------------
SELECT COALESCE(pj.png_jawab, rp.kd_pj) AS penjamin, COUNT(*) AS transaksi,
      SUM(bp.besar_cicilan) AS diterima, SUM(bp.diskon_piutang) AS diskon, SUM(bp.tidak_terbayar) AS tidak_terbayar
  FROM bayar_piutang bp
  JOIN reg_periksa rp ON rp.no_rawat = bp.no_rawat
  LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
  WHERE bp.tgl_bayar BETWEEN @mulai AND @selesai
  GROUP BY rp.kd_pj, pj.png_jawab ORDER BY diterima DESC;

-- ----------------------------------------------------------------
-- 39. Komposisi tagihan
-- ----------------------------------------------------------------
SELECT b.status AS kategori, COUNT(DISTINCT b.no_rawat) AS pasien, SUM(b.totalbiaya) AS total
  FROM billing b
  WHERE b.tgl_byr BETWEEN @mulai AND @selesai
    AND b.status NOT IN ('-', 'Tagihan') AND b.status NOT LIKE 'Ttl%'
  GROUP BY b.status ORDER BY total DESC;

-- ----------------------------------------------------------------
-- 40. Pendapatan per poliklinik
-- ----------------------------------------------------------------
SELECT pl.nm_poli AS poliklinik, COUNT(DISTINCT b.no_rawat) AS pasien, SUM(b.totalbiaya) AS pendapatan
  FROM billing b
  JOIN reg_periksa rp ON rp.no_rawat = b.no_rawat
  JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
  WHERE b.tgl_byr BETWEEN @mulai AND @selesai AND b.status NOT IN ('-', 'Tagihan') AND b.status NOT LIKE 'Ttl%'
  GROUP BY rp.kd_poli, pl.nm_poli ORDER BY pendapatan DESC;

-- ----------------------------------------------------------------
-- 41. Pendapatan per dokter
-- ----------------------------------------------------------------
SELECT d.nm_dokter AS dokter, COUNT(DISTINCT b.no_rawat) AS pasien, SUM(b.totalbiaya) AS pendapatan
  FROM billing b
  JOIN reg_periksa rp ON rp.no_rawat = b.no_rawat
  JOIN dokter d ON d.kd_dokter = rp.kd_dokter
  WHERE b.tgl_byr BETWEEN @mulai AND @selesai AND b.status NOT IN ('-', 'Tagihan') AND b.status NOT LIKE 'Ttl%'
  GROUP BY rp.kd_dokter, d.nm_dokter ORDER BY pendapatan DESC LIMIT 20;

-- ----------------------------------------------------------------
-- 42. Jasa tindakan dokter
-- ----------------------------------------------------------------
SELECT d.nm_dokter AS dokter, COUNT(*) AS tindakan, SUM(x.jasa) AS jasa_tindakan
  FROM (SELECT kd_dokter, tarif_tindakandr AS jasa FROM rawat_jl_dr WHERE tgl_perawatan BETWEEN @mulai AND @selesai UNION ALL SELECT kd_dokter, tarif_tindakandr AS jasa FROM rawat_jl_drpr WHERE tgl_perawatan BETWEEN @mulai AND @selesai UNION ALL SELECT kd_dokter, tarif_tindakandr AS jasa FROM rawat_inap_dr WHERE tgl_perawatan BETWEEN @mulai AND @selesai UNION ALL SELECT kd_dokter, tarif_tindakandr AS jasa FROM rawat_inap_drpr WHERE tgl_perawatan BETWEEN @mulai AND @selesai
        UNION ALL SELECT operator1, biayaoperator1 FROM operasi WHERE tgl_operasi BETWEEN @mulai AND CONCAT(@selesai, ' 23:59:59')) x
  JOIN dokter d ON d.kd_dokter = x.kd_dokter
  GROUP BY x.kd_dokter, d.nm_dokter ORDER BY jasa_tindakan DESC;

-- ----------------------------------------------------------------
-- 43. Hutang supplier
-- ----------------------------------------------------------------
SELECT COALESCE(s.nama_suplier, p.kode_suplier) AS supplier, COUNT(*) AS faktur,
      SUM(p.tagihan) AS tagihan, SUM(COALESCE(bp.dibayar, 0)) AS dibayar, SUM(p.tagihan - COALESCE(bp.dibayar, 0)) AS sisa,
      SUM(CASE WHEN p.tgl_tempo < CURDATE() THEN p.tagihan - COALESCE(bp.dibayar, 0) ELSE 0 END) AS lewat_jatuh_tempo
  FROM pemesanan p
  LEFT JOIN datasuplier s ON s.kode_suplier = p.kode_suplier
  LEFT JOIN (SELECT no_faktur, SUM(besar_bayar) AS dibayar FROM bayar_pemesanan GROUP BY no_faktur) bp ON bp.no_faktur = p.no_faktur
  WHERE p.status <> 'Sudah Dibayar'
  GROUP BY p.kode_suplier, s.nama_suplier
  HAVING sisa > 0
  ORDER BY sisa DESC;

-- ----------------------------------------------------------------
-- 44. Pengeluaran harian
-- ----------------------------------------------------------------
SELECT COALESCE(k.nama_kategori, ph.kode_kategori) AS kategori, COUNT(*) AS transaksi, SUM(ph.biaya) AS total
  FROM pengeluaran_harian ph LEFT JOIN kategori_pengeluaran_harian k ON k.kode_kategori = ph.kode_kategori
  WHERE ph.tanggal BETWEEN @mulai AND CONCAT(@selesai, ' 23:59:59')
  GROUP BY ph.kode_kategori, k.nama_kategori ORDER BY total DESC;

-- ----------------------------------------------------------------
-- 45. Margin farmasi
-- ----------------------------------------------------------------
SELECT d.status AS layanan, SUM(d.total) AS penjualan, SUM(d.h_beli * d.jml) AS modal,
      SUM(d.total - d.h_beli * d.jml) AS margin
  FROM detail_pemberian_obat d WHERE d.tgl_perawatan BETWEEN @mulai AND @selesai
  GROUP BY d.status;

-- ----------------------------------------------------------------
-- 46. Tren pendapatan 12 bulan
-- ----------------------------------------------------------------
SELECT bulan, SUM(bayar) AS pembayaran, SUM(piutang) AS piutang, SUM(bayar + piutang) AS total FROM (
      SELECT DATE_FORMAT(n.tanggal, '%Y-%m') AS bulan, d.besar_bayar AS bayar, 0 AS piutang FROM nota_jalan n JOIN detail_nota_jalan d ON d.no_rawat = n.no_rawat WHERE n.tanggal BETWEEN DATE_SUB(DATE_FORMAT(@selesai, '%Y-%m-01'), INTERVAL 11 MONTH) AND @selesai
      UNION ALL SELECT DATE_FORMAT(n.tanggal, '%Y-%m'), d.besar_bayar, 0 FROM nota_inap n JOIN detail_nota_inap d ON d.no_rawat = n.no_rawat WHERE n.tanggal BETWEEN DATE_SUB(DATE_FORMAT(@selesai, '%Y-%m-01'), INTERVAL 11 MONTH) AND @selesai
      UNION ALL SELECT DATE_FORMAT(tgl_piutang, '%Y-%m'), 0, totalpiutang FROM piutang_pasien WHERE tgl_piutang BETWEEN DATE_SUB(DATE_FORMAT(@selesai, '%Y-%m-01'), INTERVAL 11 MONTH) AND @selesai
  ) x GROUP BY bulan ORDER BY bulan;

-- ----------------------------------------------------------------
-- 47. Tarif INA-CBG vs biaya RS (inacbg_ringkas)
-- ----------------------------------------------------------------
SELECT CASE x.jnspelayanan WHEN '1' THEN 'Rawat inap' ELSE 'Rawat jalan' END AS layanan,
      COUNT(*) AS sep, SUM(x.tarif IS NOT NULL) AS sudah_grouping, SUM(x.tarif IS NOT NULL AND x.biaya IS NOT NULL) AS dibandingkan,
      SUM(CASE WHEN x.tarif IS NOT NULL AND x.biaya IS NOT NULL THEN x.tarif END) AS tarif_inacbg,
      SUM(CASE WHEN x.tarif IS NOT NULL AND x.biaya IS NOT NULL THEN x.biaya END) AS biaya_rs,
      SUM(CASE WHEN x.tarif IS NOT NULL AND x.biaya IS NOT NULL THEN x.tarif - x.biaya END) AS selisih
  FROM (SELECT s.no_sep, s.no_rawat, s.jnspelayanan,
          COALESCE(g12.code_cbg, g1.code_cbg) AS cbg, COALESCE(g12.deskripsi, g1.deskripsi) AS deskripsi,
          COALESCE(g12.tarif, g1.tarif) AS tarif, b.biaya
       FROM bridging_sep s
       LEFT JOIN inacbg_grouping_stage12 g12 ON g12.no_sep = s.no_sep
       LEFT JOIN inacbg_grouping_stage1 g1 ON g1.no_sep = s.no_sep
       LEFT JOIN (SELECT no_rawat, SUM(totalbiaya) AS biaya FROM billing
                  WHERE no_rawat IN (SELECT no_rawat FROM bridging_sep WHERE tglsep BETWEEN @mulai AND @selesai)
                    AND status NOT IN ('-', 'Tagihan') AND status NOT LIKE 'Ttl%'
                  GROUP BY no_rawat) b ON b.no_rawat = s.no_rawat
       WHERE s.tglsep BETWEEN @mulai AND @selesai) x GROUP BY x.jnspelayanan ORDER BY x.jnspelayanan;

-- ----------------------------------------------------------------
-- 48. Tarif INA-CBG vs biaya RS (inacbg_cbg)
-- ----------------------------------------------------------------
SELECT x.cbg, MAX(x.deskripsi) AS deskripsi, COUNT(*) AS kasus,
      SUM(x.tarif) AS tarif_inacbg, SUM(x.biaya) AS biaya_rs, SUM(x.tarif - x.biaya) AS selisih
  FROM (SELECT s.no_sep, s.no_rawat, s.jnspelayanan,
          COALESCE(g12.code_cbg, g1.code_cbg) AS cbg, COALESCE(g12.deskripsi, g1.deskripsi) AS deskripsi,
          COALESCE(g12.tarif, g1.tarif) AS tarif, b.biaya
       FROM bridging_sep s
       LEFT JOIN inacbg_grouping_stage12 g12 ON g12.no_sep = s.no_sep
       LEFT JOIN inacbg_grouping_stage1 g1 ON g1.no_sep = s.no_sep
       LEFT JOIN (SELECT no_rawat, SUM(totalbiaya) AS biaya FROM billing
                  WHERE no_rawat IN (SELECT no_rawat FROM bridging_sep WHERE tglsep BETWEEN @mulai AND @selesai)
                    AND status NOT IN ('-', 'Tagihan') AND status NOT LIKE 'Ttl%'
                  GROUP BY no_rawat) b ON b.no_rawat = s.no_rawat
       WHERE s.tglsep BETWEEN @mulai AND @selesai) x WHERE x.tarif IS NOT NULL AND x.biaya IS NOT NULL
  GROUP BY x.cbg ORDER BY selisih;

