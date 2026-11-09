<?php

/*
 * Modul Closing akuntansi: merapikan data SIMRS Khanza sampai tanggal cut off,
 * memetakannya ke COA baru, lalu menghasilkan saldo awal untuk SIMRS baru.
 * Nilai di sini hanya bawaan. Tanggal cut off dan awal tahun buku bisa diubah Akunting di halaman Closing.
 */
return [
    // Hari terakhir Khanza dipakai penuh. SIMRS baru go-live 2 November 2026; setelah itu Khanza hanya untuk
    // pasien rawat inap yang sudah dirawat sebelum go-live (ranap lanjutan) sampai pulang.
    'cutoff' => env('CLOSING_CUTOFF', '2026-11-01'),
    'year_start' => env('CLOSING_YEAR_START', '2026-01-01'), // awal tahun buku yang akan ditutup

    // Batas waktu per query saat mengambil data closing (detik). Lebih longgar dari dasbor karena dijalankan sesekali.
    'query_timeout' => (int) env('CLOSING_QUERY_TIMEOUT', 180),

    // Akun COA baru untuk mencocokkan buku besar dengan rincian (rekonsiliasi).
    'recon' => [
        'piutang' => ['1110', '1111', '1112'],
        'hutang' => ['2101', '2102', '2103'],
        'persediaan' => ['1120', '1121', '1122', '1123'],
    ],
    // Akun rincian piutang menurut nama penjamin (dicek berurutan, tidak cocok = akun terakhir)
    'piutang_accounts' => [['BPJS', '1111'], ['UMUM', '1110'], ['PASIEN', '1110'], ['', '1112']],
    // Akun persediaan menurut jenis barang Khanza
    'stock_accounts' => [['OBAT', '1120'], ['', '1121']],
    // Akun hutang menurut jenis pemesanan
    'payable_accounts' => ['Medis' => '2101', 'Non medis' => '2103'],

    // Akun penampung saat laba rugi ditutup pada saldo awal SIMRS baru
    'current_earnings' => '3202',
    'retained_earnings' => '3201',
];
