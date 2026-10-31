{{-- Tur panduan halaman Rekap persediaan --}}
@php
  $steps = [
    ['title' => 'Rekap persediaan', 'text' => 'Halaman ini menggantikan kolom-kolom rekap di sisi kanan file Excel: total masuk, keluar, stok akhir, ROP, dan nilai stok. Semua dihitung otomatis dari Input stok harian, tidak ada rumus yang perlu dijaga.'],
    ['el' => '#tKind', 'title' => 'Obat atau BMHP', 'text' => 'Tampilkan rekap <b>Obat</b> atau <b>BMHP</b> (bahan medis habis pakai). Keduanya dihitung terpisah.'],
    ['el' => '#tUnit', 'title' => 'Pilih unit', 'text' => 'Tampilkan rekap <b>Gudang Farmasi</b> atau <b>Farmasi Sentral</b>.'],
    ['el' => '.stk-date', 'title' => 'Pilih bulan', 'text' => 'Rekap dihitung per bulan. Untuk bulan berjalan, angka dihitung sampai hari ini.'],
    ['el' => '#tStats', 'title' => 'Ringkasan', 'text' => '<ul><li><b>Perlu dipesan</b>: stok di bawah titik pesan ulang.</li><li><b>Habis / minus</b>: stok 0 atau minus (tanda ada isian yang salah).</li><li><b>ED ≤ 90 hari</b>: obat yang segera atau sudah kedaluwarsa.</li><li><b>Nilai stok akhir</b>: stok × harga satuan.</li></ul>Klik kartu untuk langsung menyaring daftar di bawah.'],
    ['el' => '#rpQ', 'title' => 'Cari dan saring', 'text' => 'Cari nama obat, pilih golongan, atau saring status. Pilihan <b>Ada transaksi bulan ini</b> berguna untuk pemeriksaan cepat.'],
    ['el' => '#rpTbl thead', 'title' => 'Arti kolom', 'text' => '<ul><li><b>Selisih SO</b>: hasil hitung fisik dikurangi stok sistem.</li><li><b>Pakai/hari</b>: total keluar dibagi jumlah hari.</li><li><b>Safety</b>: pakai/hari × '.\App\Support\StockLedger::SAFETY_DAYS.' hari.</li><li><b>ROP</b>: pakai/hari × '.\App\Support\StockLedger::ROP_DAYS.' hari. Bila stok akhir ≤ ROP, status menjadi <b>Perlu pesan</b>.</li></ul>Arahkan kursor ke judul kolom untuk melihat keterangannya lagi.'],
    ['el' => '.rp-row a.main', 'title' => 'Kartu stok', 'text' => 'Klik nama obat untuk membuka kartu stoknya: riwayat masuk, keluar, dan stock opname per hari, lengkap dengan nama petugas yang mengisi.'],
    ['el' => '#tExport', 'title' => 'Unduh untuk Excel', 'text' => 'Mengunduh rekap bulan ini dalam bentuk CSV yang bisa dibuka di Excel, misalnya untuk dicocokkan dengan laporan stok SIMRS selama masa pengisian paralel.'],
  ];
@endphp
@push('tour')<script>window.TOUR = @json(['key' => 'stock-report', 'guide' => route('admin.help').'#stock-3', 'steps' => $steps]);</script>@endpush
