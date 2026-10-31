{{-- Tur panduan halaman Kartu stok --}}
@php
  $steps = [
    ['title' => 'Kartu stok', 'text' => 'Riwayat lengkap satu obat dalam satu bulan, seperti kartu stok di rak.'],
    ['el' => '#tEnds', 'title' => 'Stok saat ini', 'text' => 'Stok obat ini di setiap unit hari ini, beserta harga dan ED terdekat. Klik kartu unit untuk melihat riwayat unit tersebut.'],
    ['el' => '#tHist', 'title' => 'Riwayat harian', 'text' => 'Setiap baris adalah satu tanggal yang diisi: hasil SO (bila ada), masuk, keluar, dan sisa setelah hari itu, beserta catatan, nama petugas, dan jam pengisian.'],
    ['el' => '#tHist tbody a', 'title' => 'Koreksi isian', 'text' => 'Klik tanggal untuk membuka lembar isian hari itu bila ada angka yang perlu diperbaiki.'],
    ['el' => '.vh-act .btn', 'title' => 'Kembali ke rekap', 'text' => 'Kembali ke daftar semua obat.'],
  ];
@endphp
@push('tour')<script>window.TOUR = @json(['key' => 'stock-card', 'guide' => route('admin.help').'#stock-3', 'steps' => $steps]);</script>@endpush
