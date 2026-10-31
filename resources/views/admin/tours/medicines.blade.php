{{-- Tur panduan halaman Data obat (daftar) --}}
@php
  $steps = [
    ['title' => 'Data obat', 'text' => 'Daftar obat dan alkes yang muncul di lembar Input stok harian. Daftar awal diambil dari file Excel persediaan.'],
    ['el' => '.vh-act .btn.pri', 'title' => 'Tambah obat', 'text' => 'Klik untuk menambahkan obat baru. Obat baru langsung muncul di lembar isian kedua unit.'],
    ['el' => '.list-tools', 'title' => 'Cari dan saring', 'text' => 'Cari nama obat, lalu saring golongan atau status. Klik <b>Terapkan</b> setelah memilih.'],
    ['el' => '.tbl tbody tr td:nth-child(3)', 'title' => 'Harga satuan', 'text' => 'Harga per satuan terkecil termasuk PPN. Tanda <b>Belum diisi</b> berarti nilai stok obat ini belum bisa dihitung.'],
    ['el' => '.tbl tbody tr td:nth-child(4)', 'title' => 'Tanggal ED', 'text' => 'Oranye: kurang dari 90 hari lagi. Merah: sudah kedaluwarsa.'],
    ['el' => '.tbl tbody tr .switch', 'title' => 'Aktif', 'text' => 'Matikan untuk menyembunyikan obat yang tidak dipakai lagi dari lembar isian. Riwayat stoknya tetap tersimpan.'],
    ['el' => '.tbl tbody tr td.acts', 'title' => 'Ubah atau hapus', 'text' => 'Ikon pensil untuk mengubah data. Obat yang sudah punya riwayat stok tidak bisa dihapus, cukup dinonaktifkan.'],
  ];
@endphp
@push('tour')<script>window.TOUR = @json(['key' => 'medicines', 'guide' => route('admin.help').'#stock-4', 'steps' => $steps]);</script>@endpush
