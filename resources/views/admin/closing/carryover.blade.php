@extends('layouts.admin', ['current' => 'closing'])
@use('App\Support\Fmt', 'F')
@section('title', 'Ranap lanjutan · Closing')
@section('content')
@include('admin.closing._nav', ['actions' => $book->snap ? [['Ringkasan per bulan (Excel)', route('admin.closing.export', 'ranap-lanjutan'), true], ['Rincian jurnal (Excel)', route('admin.closing.export', 'ranap-lanjutan-rinci'), false]] : []])
@php
  $cut = \Illuminate\Support\Carbon::parse($book->settings['cutoff']);
  [$rd, $rk] = $co['total']['Ranap lanjutan']; [$od, $ok] = $co['total']['Lainnya'];
@endphp
@if(!$book->snap)
  <div class="card mg-card"><p class="mg-empty">Ambil data dari Khanza terlebih dahulu di halaman Ringkasan.</p></div>
@else
<div class="stk-note">Pasien yang masih dirawat pada akhir {{ $cut->translatedFormat('d F Y') }} tetap dilayani di Khanza sampai pulang. Transaksinya setelah cut off
  <b>tidak masuk</b> neraca saldo dan saldo awal SIMRS baru, karena terjadi setelah tanggal saldo awal. Unduh ringkasannya per bulan per akun COA lalu catat sebagai
  jurnal memorial di SIMRS baru (bulan November/Desember), supaya laporan setahun lengkap dan tidak ada yang tercatat dua kali.
  Ambil ulang data Khanza setelah semua pasien ini pulang.</div>

<div class="mg-kpis">
  <div class="mg-kpi"><small>Dirawat saat cut off</small><b class="sm">{{ F::n(count($co['patients'])) }}</b><span>pasien ranap lanjutan</span></div>
  <div class="mg-kpi" style="border-top-color:{{ $co['open'] ? 'var(--amber)' : 'var(--green)' }}"><small>Belum pulang</small><b class="sm">{{ F::n($co['open']) }}</b><span>per data diambil</span></div>
  <div class="mg-kpi"><small>Pendapatan setelah cut off</small><b class="sm">{{ F::rps(collect($co['patients'])->sum('pendapatan')) }}</b><span>dari jurnal Khanza pasien ini</span></div>
  <div class="mg-kpi"><small>Jurnal ranap lanjutan</small><b class="sm">{{ F::rps($rd) }}</b><span>total debet{{ abs($rd - $rk) >= 1 ? ' · kredit '.F::rps($rk) : '' }}</span></div>
  @if($co['other'])<div class="mg-kpi" style="border-top-color:var(--amber)"><small>Jurnal lain setelah cut off</small><b class="sm">{{ F::n(count($co['other'])) }}</b><span>total debet {{ F::rps($od) }}</span></div>@endif
</div>
@if($co['unmapped'])<div class="flash err">{{ $co['unmapped'] }} baris jurnal memakai rekening Khanza yang belum dipetakan. <a href="{{ route('admin.closing.mapping', ['f' => 'belum']) }}">Petakan sekarang</a>.</div>@endif

<div class="card mg-card">
  <div class="mg-h"><h2>Pasien dirawat saat cut off</h2></div>
  <p class="mg-note">Pendapatan pasien ini tercatat di Khanza saat tagihan dibuat (setelah cut off). Bila kebijakan Akunting mengakui pendapatan hari rawat sampai {{ $cut->translatedFormat('d M') }}
    sebagai pendapatan sebelum cut off (akrual), buat jurnal penyesuaian sendiri; jangan lupa porsi itu dikurangi dari jurnal ranap lanjutan saat dicatat di SIMRS baru.</p>
  <div class="table-scroll"><table class="mg-tbl cl-tbl">
    <thead><tr><th>No. rawat</th><th>Pasien</th><th>Penjamin</th><th>Masuk</th><th>Status sekarang</th><th class="r">Jurnal</th><th class="r">Pendapatan setelah cut off</th></tr></thead>
    <tbody>
    @forelse($co['patients'] as $p)
      <tr><td>{{ $p['no_rawat'] }}</td><td>{{ $p['pasien'] }} <small>{{ $p['no_rkm_medis'] }}</small></td><td>{{ $p['penjamin'] }}</td><td>{{ $p['tgl_masuk'] }}</td>
        <td>@if($p['masih_dirawat'] ?? true)<span class="tag warn">Masih dirawat</span>@else<span class="tag ok">Pulang {{ $p['tgl_pulang'] }}</span>@endif</td>
        <td class="r">{{ $p['jurnal'] ?: '' }}</td>
        <td class="r">@if($p['pendapatan']){{ F::n($p['pendapatan']) }}@elseif(!($p['masih_dirawat'] ?? true))<span class="tag bad" title="Sudah pulang tetapi belum ada jurnal pendapatan di Khanza setelah cut off">belum ada jurnal</span>@endif</td></tr>
    @empty
      <tr><td colspan="7" class="mg-empty">Tidak ada pasien yang dirawat saat cut off.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>

<div class="card mg-card">
  <div class="mg-h"><h2>Ringkasan per bulan per akun COA</h2><a href="{{ route('admin.closing.export', 'ranap-lanjutan') }}" data-noload>Unduh (Excel)</a></div>
  <p class="mg-note">Satu kelompok bulan = satu jurnal memorial di SIMRS baru. Nilai sudah dinetokan per akun.</p>
  @php $groups = collect($co['byCoa'])->groupBy(fn ($r) => $r['kelompok'].' · '.F::month($r['bulan'])); @endphp
  @forelse($groups as $label => $g)
    <h3 style="font-size:14px;margin:14px 0 6px" @if(str_starts_with($label, 'Lainnya')) id="lainnya" @endif>{{ $label }}</h3>
    <table class="mg-tbl cl-tbl"><thead><tr><th>Akun COA</th><th class="r">Debit</th><th class="r">Kredit</th></tr></thead><tbody>
      @foreach($g as $r)<tr><td>@if($r['code'])<b>{{ $r['code'] }}</b> {{ $r['name'] }}@else<span class="tag bad">Belum dipetakan</span>@endif</td>
        <td class="r">{{ $r['debit'] ? F::n($r['debit'], 2) : '' }}</td><td class="r">{{ $r['credit'] ? F::n($r['credit'], 2) : '' }}</td></tr>@endforeach
      <tr class="tot"><td>Total</td><td class="r">{{ F::n($g->sum('debit'), 2) }}</td><td class="r">{{ F::n($g->sum('credit'), 2) }}</td></tr>
    </tbody></table>
  @empty
    <p class="mg-empty">Belum ada jurnal Khanza setelah cut off.</p>
  @endforelse
</div>

@if($co['other'])
<div class="card mg-card">
  <div class="mg-h"><h2>Jurnal lain setelah cut off</h2></div>
  <p class="mg-note">Jurnal ini tidak terkait pasien ranap lanjutan, misalnya pelunasan piutang lama, pembayaran supplier, atau pengeluaran yang masih dicatat lewat Khanza.
    Periksa satu per satu: bila transaksinya juga dicatat di SIMRS baru, jangan dicatat ulang dari sini.</p>
  <div class="table-scroll mg-scroll"><table class="mg-tbl cl-tbl"><thead><tr><th>Tanggal</th><th>No. jurnal</th><th>Keterangan</th><th>Rekening</th><th class="r">Debet</th><th class="r">Kredit</th></tr></thead>
    <tbody>@foreach(collect($co['lines'])->where('kelompok', 'Lainnya')->take(300) as $l)<tr><td>{{ $l['tgl_jurnal'] }}</td><td>{{ $l['no_jurnal'] }} <small>{{ $l['no_bukti'] }}</small></td><td>{{ $l['keterangan'] }}</td>
      <td>{{ $l['coa'] ?? $l['kd_rek'] }} {{ $l['coa_name'] ?? $l['nm_rek'] }}</td><td class="r">{{ $l['debet'] ? F::n($l['debet'], 2) : '' }}</td><td class="r">{{ $l['kredit'] ? F::n($l['kredit'], 2) : '' }}</td></tr>@endforeach</tbody></table></div>
</div>
@endif
@endif
@endsection
