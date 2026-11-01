{{-- Tur panduan halaman detail kunjungan pasien --}}
@php
  $steps = [
    ['title' => 'Detail kunjungan', 'text' => 'Semua yang tercatat di SIMRS Khanza untuk satu kunjungan: diagnosa, pemeriksaan dokter dan perawat, tindakan, obat, hasil lab dan radiologi, rawat inap, operasi, dan resume.'],
    ['el' => '#tInfo', 'title' => 'Info kunjungan', 'text' => 'Tanggal, poli, dokter, penjamin, dan umur pasien saat berobat.'],
    ['el' => '.pt-nav', 'title' => 'Lompat ke bagian', 'text' => 'Klik untuk langsung ke bagian tertentu. Angka menunjukkan jumlah catatan; bagian yang pudar berarti kosong.'],
    ['el' => '#tSoap', 'title' => 'Pemeriksaan (SOAP)', 'text' => 'Catatan dokter dan perawat berurutan waktu: tanda vital, <b>S</b>ubjektif (keluhan), <b>O</b>bjektif (pemeriksaan), <b>A</b>sesmen, dan <b>P</b>lan (rencana).'],
    ['el' => '#tLab', 'title' => 'Hasil laboratorium', 'text' => 'Hasil per pemeriksaan dengan nilai rujukan. Angka merah ditandai tinggi/rendah oleh laboratorium.'],
  ];
@endphp
@push('tour')<script>window.TOUR = @json(['key' => 'patients-visit', 'guide' => route('admin.help').'#patients', 'steps' => $steps]);</script>@endpush
