<a id="simrs" class="anchor"></a>

# Bab 20. Integrasi SIMRS

<p class="lead">Halaman untuk memantau dan mengatur hubungan website dengan SIMRS. Menu ini biasanya hanya untuk tim IT atau Administrator.</p>

<figure><a href="/docs/img/simrs.jpg" target="_blank"><img src="/docs/img/simrs.jpg" width="1200" height="1295" alt="Gambar 20.1  Halaman Integrasi SIMRS." loading="lazy"></a><figcaption>Gambar 20.1  Halaman Integrasi SIMRS.</figcaption></figure>

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
