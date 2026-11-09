<a id="cl-coa" class="anchor"></a>

# Bab 5. COA baru

<p class="lead">Daftar akun yang dipakai SIMRS baru. Modul sudah terisi dengan template COA Serenavita (103 akun, 84 di antaranya akun detail).</p>

## 5.1 Membaca daftar COA

| Kolom | Arti |
|---|---|
| Kode | Account Number. |
| Nama akun | Menjorok ke kanan sesuai tingkatnya. |
| Jenis | **Parents/Header** (induk utama), **Sub Parents/Sub Header** (sub induk), atau **Child/Detail**. Hanya akun Child/Detail yang bisa dipakai pada pemetaan dan jurnal. |
| Kategori | Aset, Kewajiban, Ekuitas, Pendapatan, Beban, atau Lain-lain. Menentukan letak akun di neraca atau laba rugi. |
| Parent | Kode akun induk. |
| Rekening Khanza | Jumlah rekening Khanza yang dipetakan ke akun ini. |

Kategori Aset, Kewajiban, dan Ekuitas masuk **neraca**. Pendapatan, Beban, dan Lain-lain masuk **laba rugi**.

## 5.2 Mengganti COA dengan template baru

Lakukan bila COA di SIMRS baru berubah, misalnya ada akun tambahan.

1. Siapkan file Excel (.xlsx) dengan format template COA SIMRS baru. Kolom yang dibaca: **ID**, **Account Number**, **Account Name**, **Company**, **Parent Account**, **Is Group/Type Akun**, **Kategori Akun**.
2. Di halaman **COA baru**, pilih file di kotak **Ganti COA dari file template**.
3. Tekan **Unggah COA** lalu konfirmasi.

Aturan file:

- **Account Number** harus unik.
- **Parent Account** boleh diisi dengan ID induk (contoh `1.1 Aset Lancar`) atau kode induk (contoh `1100`). Induk harus ada di file yang sama.
- **Is Group/Type Akun** berisi `Parents/Header`, `Sub Parents/Sub Header`, atau `Child/Detail`.
- **Kategori Akun** berisi Aset, Kewajiban, Ekuitas, Pendapatan, Beban, atau Lain-lain.

Mengunggah COA **mengganti seluruh daftar**. Pemetaan tetap disimpan dan dicocokkan berdasarkan kode. Bila ada pemetaan ke kode yang tidak ada lagi di COA baru, muncul pesan merah; rekening itu tampil dengan tanda *kode tidak ada di COA* di halaman Pemetaan akun dan harus dipetakan ulang.

Tekan **Unduh COA (Excel)** untuk menyimpan daftar COA yang sedang dipakai.
