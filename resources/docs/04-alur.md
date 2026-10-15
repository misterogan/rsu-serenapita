<a id="alur" class="anchor"></a>

# Bab 4. Alur kerja

<p class="lead">Bab ini menjelaskan urutan pekerjaan: apa yang dikerjakan sekali di awal, apa yang dikerjakan setiap hari, dan bagaimana data mengalir dari pasien ke petugas.</p>

<a id="alur-1" class="anchor"></a>

## 4.1 Persiapan awal (dilakukan sekali)

Saat website pertama kali diserahkan, isinya masih data contoh. Ganti dengan data asli rumah sakit mengikuti urutan berikut, supaya tidak ada yang terlewat.

<ol class="flow"><li>Profil & kontak (logo, alamat, telepon)</li><li>Pengguna: buat akun petugas</li><li>Hak akses tiap peran</li><li>Menu & tampilan</li><li>Kotak info & jam</li><li>Slider beranda</li><li>Layanan & fasilitas</li><li>Poliklinik (deskripsi)</li><li>Profil dokter (foto & profil)</li><li>Kategori berita</li><li>Berita: hapus contoh, tulis yang asli</li><li>Chatbot Serena (FAQ)</li><li>Pengaturan live chat</li><li>Formulir janji temu</li><li>Integrasi SIMRS (tim IT)</li><li>Cek hasil di website</li></ol>

| Urutan | Menu | Yang dikerjakan | Bab |
|---|---|---|---|
| 1 | Profil & kontak | Unggah logo, isi alamat lengkap, nomor telepon resmi, email, jam loket, dan jam besuk. | 19 |
| 2 | Pengguna | Buat akun untuk setiap petugas. Ganti kata sandi akun bawaan dan nonaktifkan akun contoh yang tidak dipakai. | 21 |
| 3 | Hak akses | Tentukan menu yang boleh dibuka oleh tiap peran, misalnya Humas dan Pendaftaran. | 22 |
| 4 | Menu & tampilan | Atur nama dan urutan menu situs, lalu bagian beranda yang ingin ditampilkan. | 17 |
| 5 | Kotak info & jam | Isi teks tiga kotak berwarna di beranda dan jam pelayanan. | 10 |
| 6 | Slider beranda | Tulis 2 sampai 3 pesan utama rumah sakit. | 9 |
| 7 | Layanan & fasilitas | Sesuaikan daftar layanan dengan fasilitas yang benar-benar ada. | 11 |
| 8 | Poliklinik | Tulis deskripsi setiap poli dan pilih poli unggulan. | 13 |
| 9 | Profil dokter | Unggah foto dan tulis profil singkat setiap dokter. | 12 |
| 10 | Kategori & Berita | Hapus berita contoh dan terbitkan berita asli. | 7, 8 |
| 11 | Chatbot | Lengkapi daftar FAQ: parkir, tarif, fasilitas, dan sebagainya. | 18 |
| 12 | Live chat | Isi nama petugas, jam layanan, dan balasan cepat. | 6 |
| 13 | Formulir janji temu | Atur data yang wajib diisi pasien dan teks persetujuan. | 16 |
| 14 | Integrasi SIMRS | Dikerjakan tim IT bersama vendor SIMRS. | 20 |

<div class="callout penting"><b>Penting</b><p>Akun bawaan memakai kata sandi <b>serenapita123</b>, yang tercatat di dokumen teknis. Ganti kata sandi semua akun bawaan pada hari pertama.</p></div>

<a id="alur-2" class="anchor"></a>

## 4.2 Rutinitas harian

| Waktu | Kegiatan | Menu |
|---|---|---|
| Awal jam kerja | Buka Dasbor. Lihat jumlah janji temu hari ini, live chat yang menunggu, dan dokter yang cuti. | Dasbor |
| Sepanjang jam layanan | Pantau angka merah di menu Live chat. Balas pesan pasien secepatnya, lalu klik <b>Selesaikan</b> kalau urusannya sudah beres. | Live chat |
| Setiap ada kegiatan | Tulis berita atau pengumuman, misalnya kegiatan cek kesehatan gratis atau perubahan jadwal. | Berita |
| Akhir jam kerja | Pastikan tidak ada live chat yang masih berstatus Menunggu atau Aktif tanpa balasan. Keluar dari backoffice. | Live chat |
| Bila ada perubahan | Bila nomor telepon atau jam besuk berubah, segera perbarui supaya informasi di website dan jawaban chatbot tetap benar. | Profil & kontak |

<a id="alur-3" class="anchor"></a>

## 4.3 Rutinitas mingguan dan bulanan

- <b>Mingguan</b>: periksa halaman Jadwal dokter di website. Laporkan ke bagian SIMRS bila ada jadwal yang tidak sesuai.
- <b>Mingguan</b>: baca pertanyaan pasien di live chat yang sering berulang, lalu tambahkan jawabannya ke FAQ chatbot.
- <b>Bulanan</b>: periksa Log aktivitas untuk memastikan tidak ada perubahan yang mencurigakan.
- <b>Bulanan</b>: periksa daftar Pengguna. Nonaktifkan akun petugas yang sudah pindah tugas atau berhenti.

<a id="alur-4" class="anchor"></a>

## 4.4 Alur janji temu

<ol class="flow"><li>Pasien isi formulir Buat Janji di website</li><li>Website meminta jam tersedia ke SIMRS</li><li>Janji tercatat di SIMRS, pasien dapat kode & nomor antrean</li><li>Petugas pendaftaran mengonfirmasi di SIMRS</li><li>Status ikut berubah di backoffice saat sinkron</li><li>Pasien datang ke loket dengan kode janji</li></ol>

<div class="callout catatan"><b>Catatan</b><p>Janji temu <b>tidak diubah di backoffice</b>. Konfirmasi, kehadiran, dan pembatalan dikerjakan di SIMRS. Backoffice hanya untuk memantau.</p></div>

<a id="alur-5" class="anchor"></a>

## 4.5 Alur live chat

<ol class="flow"><li>Pengunjung isi nama, nomor HP, topik, dan pesan</li><li>Status: Menunggu (angka merah muncul)</li><li>Petugas klik Ambil percakapan</li><li>Status: Aktif, petugas dan pasien saling membalas</li><li>Petugas klik Selesaikan</li><li>Status: Selesai, pasien bisa mulai chat baru</li></ol>

<a id="alur-6" class="anchor"></a>

## 4.6 Alur berita

<ol class="flow"><li>Tulis berita, simpan sebagai Draf</li><li>Periksa ejaan dan foto</li><li>Ubah status jadi Terbit atau Terjadwal</li><li>Berita tampil di halaman Berita</li><li>Aktifkan Di beranda bila perlu ditonjolkan</li><li>Cek hasil dengan Lihat situs</li></ol>
