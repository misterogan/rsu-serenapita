<a id="simrs" class="anchor"></a>

# Bab 20. Integrasi SIMRS

<p class="lead">Halaman untuk memilih sumber data dokter dan jadwal, serta memantau dan mengatur hubungan website dengan SIMRS. Menu ini biasanya hanya untuk tim IT atau Administrator.</p>

<a id="simrs-1" class="anchor"></a>

## 20.1 Sumber data: manual atau SIMRS

<figure><a href="/docs/img/simrs_source.jpg" target="_blank"><img src="/docs/img/simrs_source.jpg" width="1200" height="771" alt="Gambar 20.1  Pilihan sumber data dokter dan jadwal." loading="lazy"></a><figcaption>Gambar 20.1  Pilihan sumber data dokter dan jadwal.</figcaption></figure>

|  | Mode manual | Mode SIMRS |
|---|---|---|
| Kapan dipakai | Sekarang, selama SIMRS belum terhubung | Setelah API SIMRS siap |
| Dokter, poli, jadwal | Diisi di Profil dokter dan Poliklinik | Diambil otomatis dari SIMRS, terkunci |
| Cuti dokter | Dicatat di menu Cuti dokter | Diambil dari SIMRS |
| Jam tersedia dan kuota | Dari jadwal, jarak jam, dan kuota di Formulir janji temu | Dari SIMRS |
| Status janji temu | Diubah petugas di menu Janji temu | Diubah di SIMRS, ikut ter-update saat sinkron |
| Foto, profil, tampilan | Backoffice | Backoffice |

### Pindah dari mode manual ke SIMRS

1. Pastikan file .env di server sudah berisi SIMRS_DRIVER=http, SIMRS_BASE_URL, dan SIMRS_TOKEN, lalu klik <b>Uji koneksi</b> sampai berhasil.
2. Isi <b>Kode poli di SIMRS</b> pada setiap poliklinik, dan <b>Kode dokter di SIMRS</b> pada setiap dokter, sesuai kode di SIMRS. Dengan begitu foto, profil, dan urutan yang sudah dibuat tetap terpakai.
3. Di kartu <b>Sumber data dokter dan jadwal</b>, pilih <b>SIMRS</b>, lalu klik <b>Simpan sumber data</b>. Sinkron pertama langsung berjalan.
4. Periksa menu Profil dokter dan Jadwal dokter. Dokter yang kodenya tidak ditemukan di SIMRS otomatis menjadi <b>Nonaktif</b>.

<div class="callout awas"><b>Hati-hati</b><p>Setelah pindah ke mode SIMRS, cuti yang dicatat manual digantikan data cuti dari SIMRS. Bila sinkron pertama gagal, kembalikan ke mode manual dan periksa koneksi bersama vendor SIMRS.</p></div>

<a id="simrs-2" class="anchor"></a>

## 20.2 Koneksi dan sinkronisasi (mode SIMRS)

<figure><a href="/docs/img/simrs.jpg" target="_blank"><img src="/docs/img/simrs.jpg" width="1200" height="1295" alt="Gambar 20.2  Halaman Integrasi SIMRS." loading="lazy"></a><figcaption>Gambar 20.2  Halaman Integrasi SIMRS.</figcaption></figure>

| Bagian | Keterangan |
|---|---|
| Status | <b>Terhubung</b> berarti sinkron terakhir berhasil. <b>Gagal</b> berarti ada masalah koneksi. <b>Belum diuji</b> berarti belum pernah dicoba. |
| Sinkron terakhir | Waktu terakhir data dokter, jadwal, dan cuti diambil dari SIMRS. |
| Uji koneksi | Mencoba menghubungi SIMRS dan menampilkan waktu responsnya. |
| Sinkronkan sekarang | Mengambil data terbaru dari SIMRS saat itu juga. |
| Cara menerima perubahan | <b>Webhook dari SIMRS</b>: SIMRS mengabari website setiap ada perubahan. <b>Pengecekan berkala</b>: website menanyakan ke SIMRS secara rutin. |
| Interval pengecekan | Setiap berapa menit data diambil, yaitu 1, 2, 5, atau 10 menit. Pengaturan ini juga berlaku sebagai cadangan saat webhook gagal. |
| Kirim janji temu ke SIMRS | Bila aktif, janji dari website langsung dibuat di SIMRS dan mendapat kode booking. |
| Tetap terima janji saat SIMRS tidak terjangkau | Bila aktif, janji tetap disimpan dan ditandai <b>Gagal</b> kirim, lalu bisa dikirim ulang dari menu Janji temu. Bila mati, pasien diminta mencoba lagi nanti. |
| Alamat webhook | Alamat yang diberikan kepada vendor SIMRS. |
| Pembagian data | Ringkasan data mana yang diubah di SIMRS dan mana yang diubah di backoffice. |
| Riwayat sinkronisasi | 50 kejadian terakhir beserta hasilnya, berhasil atau gagal. |

<div class="callout awas"><b>Hati-hati</b><p>Alamat server SIMRS dan token rahasia disimpan di file pengaturan server, bukan di backoffice. Perubahan pada bagian itu dikerjakan oleh tim IT.</p></div>

<div class="callout catatan"><b>Catatan</b><p>Bila muncul kotak merah <b>Mode data contoh aktif</b>, berarti website belum tersambung ke SIMRS asli dan masih memakai data dokter contoh.</p></div>
