@extends('layouts.admin', ['current' => 'closing'])
@use('App\Support\Fmt', 'F')
@section('title', 'Rekonsiliasi · Closing')
@section('content')
@include('admin.closing._nav')
@php
  $rows = fn (string $ds) => $book->snap ? $book->snap->rows($ds) : [];
  $edit = $canEdit && !$book->locked();
  $adjLink = function (array $l, string $title) {
      $credit = \App\Models\ClosingAccount::isCreditNormal($l['category'] ?? 'Aset');
      $amt = abs($l['diff']);
      // Selisih positif = buku besar lebih besar dari rincian → kurangi saldo akun
      $reduce = $l['diff'] > 0;
      $side = ($reduce xor $credit) ? 'credit' : 'debit';
      $other = $side === 'credit' ? 'debit' : 'credit';
      return route('admin.closing.adjustments.create', ['desc' => 'Penyesuaian '.$title.' akun '.$l['code'].' sesuai rincian per cut off',
          'lines' => [['coa' => $l['code'], $side => $amt, 'memo' => 'Selisih buku besar vs rincian'], ['coa' => '', $other => $amt, 'memo' => 'Isi akun lawan']]]);
  };
  $cut = \Illuminate\Support\Carbon::parse($book->settings['cutoff'])->translatedFormat('d M Y');
@endphp
@if(!$book->snap)
  <div class="card mg-card"><p class="mg-empty">Ambil data dari Khanza terlebih dahulu di halaman Ringkasan.</p></div>
@else
<p class="mg-note">Saldo buku besar (setelah pemetaan dan penyesuaian) dibandingkan dengan rincian transaksi Khanza per {{ $cut }}. Rincian ini juga menjadi saldo awal buku pembantu (piutang per pasien, hutang per faktur) di SIMRS baru.</p>

@foreach($recon as $key => $r)
<div class="card mg-card" id="{{ $key }}">
  <div class="mg-h"><h2>{{ $r['title'] }}</h2><a href="{{ route('admin.closing.export', $key) }}" data-noload>Unduh rincian (Excel)</a></div>
  @unless($r['available'])<div class="flash err">Rincian tidak bisa dibaca dari Khanza. Lihat detail error di halaman Ringkasan.</div>@endunless
  @if($key === 'persediaan')<p class="mg-note">Nilai = stok terakhir di riwayat barang Khanza pada/sebelum cut off × harga beli di data barang. Ini perkiraan; bandingkan juga dengan hasil stock opname fisik.</p>@endif
  <table class="mg-tbl cl-tbl">
    <thead><tr><th>Akun COA</th><th class="r">Buku besar</th><th class="r">Rincian Khanza</th><th class="r">Selisih</th><th></th></tr></thead>
    <tbody>
    @foreach($r['lines'] as $l)
      <tr><td><b>{{ $l['code'] }}</b> {{ $l['name'] }}</td><td class="r">{{ F::n($l['gl'], 2) }}</td><td class="r">{{ F::n($l['sub'], 2) }}</td>
        <td class="r">@if(abs($l['diff']) >= 1)<span class="tag {{ $key === 'persediaan' ? 'warn' : 'bad' }}">{{ F::n($l['diff'], 2) }}</span>@else<span class="tag ok">cocok</span>@endif</td>
        <td class="r">@if($edit && abs($l['diff']) >= 1 && $book->account($l['code']))<a class="btn sm" href="{{ $adjLink($l, strtolower($r['title'])) }}">Buat penyesuaian</a>@endif</td></tr>
    @endforeach
    <tr class="tot"><td>Total</td><td class="r">{{ F::n($r['gl'], 2) }}</td><td class="r">{{ F::n($r['sub'], 2) }}</td><td class="r">{{ F::n($r['diff'], 2) }}</td><td></td></tr>
    </tbody>
  </table>
  @php $detail = collect($rows($key))->sortByDesc(fn ($x) => $x[$key === 'persediaan' ? 'nilai' : 'sisa'])->take(200); @endphp
  @if($detail->isNotEmpty())
  <details style="margin-top:12px"><summary>Lihat rincian ({{ F::n($r['count']) }} baris{{ $r['count'] > 200 ? ', 200 terbesar ditampilkan; lengkapnya di Excel' : '' }})</summary>
    <div class="table-scroll mg-scroll"><table class="mg-tbl cl-tbl">
    @if($key === 'piutang')
      <thead><tr><th>No. rawat</th><th>Pasien</th><th>Penjamin</th><th>Tgl piutang</th><th class="r">Piutang</th><th class="r">Dibayar</th><th class="r">Sisa</th></tr></thead>
      <tbody>@foreach($detail as $x)<tr><td>{{ $x['no_rawat'] }}</td><td>{{ $x['pasien'] }} <small>{{ $x['no_rkm_medis'] }}</small></td><td>{{ $x['penjamin'] }}</td><td>{{ $x['tgl_piutang'] }}</td>
        <td class="r">{{ F::n($x['piutang']) }}</td><td class="r">{{ F::n($x['dibayar'] + $x['uang_muka']) }}</td><td class="r"><b>{{ F::n($x['sisa']) }}</b></td></tr>@endforeach</tbody>
    @elseif($key === 'hutang')
      <thead><tr><th>Jenis</th><th>No. faktur</th><th>Supplier</th><th>Jatuh tempo</th><th class="r">Tagihan</th><th class="r">Dibayar</th><th class="r">Sisa</th></tr></thead>
      <tbody>@foreach($detail as $x)<tr><td>{{ $x['jenis'] }}</td><td>{{ $x['no_faktur'] }}</td><td>{{ $x['supplier'] }}</td><td>{{ $x['jatuh_tempo'] }}</td>
        <td class="r">{{ F::n($x['tagihan']) }}</td><td class="r">{{ F::n($x['dibayar']) }}</td><td class="r"><b>{{ F::n($x['sisa']) }}</b></td></tr>@endforeach</tbody>
    @else
      <thead><tr><th>Depo</th><th>Barang</th><th>Jenis</th><th class="r">Stok</th><th class="r">Harga beli</th><th class="r">Nilai</th></tr></thead>
      <tbody>@foreach($detail as $x)<tr><td>{{ $x['depo'] }}</td><td>{{ $x['nama'] }} <small>{{ $x['kode_brng'] }}</small></td><td>{{ $x['jenis'] }}</td>
        <td class="r">{{ F::n($x['stok'], 2) }} {{ $x['satuan'] }}</td><td class="r">{{ F::n($x['harga_beli']) }}</td><td class="r"><b>{{ F::n($x['nilai']) }}</b></td></tr>@endforeach</tbody>
    @endif
    </table></div>
  </details>
  @endif
</div>
@endforeach

@php $dirawat = $rows('dirawat'); $belum = $rows('belum_ditagih'); @endphp
<div class="card mg-card" id="belum">
  <div class="mg-h"><h2>Pasien yang melewati cut off</h2><a href="{{ route('admin.closing.export', 'belum-ditagih') }}" data-noload>Unduh (Excel)</a></div>
  <p class="mg-note">Pelayanan sampai cut off yang per tanggal cut off belum ada nota dan belum menjadi piutang (nota yang dibuat setelah cut off dianggap belum ada). Pastikan pendapatannya tidak hilang dan tidak tercatat dua kali.</p>
  <div class="mg-kpis" style="margin-bottom:10px">
    <div class="mg-kpi"><small>Dirawat saat cut off</small><b class="sm">{{ F::n(count($dirawat)) }}</b><span>ranap lanjutan di Khanza</span></div>
    <div class="mg-kpi"><small>Belum ditagih</small><b class="sm">{{ F::n(count($belum)) }}</b><span>kunjungan tanpa nota/piutang</span></div>
    @foreach(collect($belum)->groupBy('layanan') as $lay => $g)<div class="mg-kpi"><small>Belum ditagih · {{ $lay }}</small><b class="sm">{{ F::n($g->count()) }}</b><span>{{ $g->where('status_periksa', 'Belum')->count() }} belum diperiksa</span></div>@endforeach
  </div>
  @if($dirawat)<p class="mg-note">Pasien yang dirawat saat cut off dilanjutkan di Khanza sampai pulang. Rinciannya di menu <a href="{{ route('admin.closing.carryover') }}">Ranap lanjutan</a>.</p>@endif
  @if($belum)
  <details><summary>Kunjungan belum ditagih ({{ count($belum) }}{{ count($belum) > 300 ? ', 300 terakhir ditampilkan' : '' }})</summary><div class="table-scroll mg-scroll"><table class="mg-tbl"><thead><tr><th>No. rawat</th><th>Tanggal</th><th>Pasien</th><th>Layanan</th><th>Poli</th><th>Penjamin</th><th>Periksa</th></tr></thead>
    <tbody>@foreach(array_slice(array_reverse($belum), 0, 300) as $x)<tr><td>{{ $x['no_rawat'] }}</td><td>{{ $x['tgl_registrasi'] }}</td><td>{{ $x['pasien'] }}</td><td>{{ $x['layanan'] }}</td><td>{{ $x['poli'] }}</td><td>{{ $x['penjamin'] }}</td><td>{{ $x['status_periksa'] }}</td></tr>@endforeach</tbody></table></div></details>
  @endif
</div>

@php $unb = $rows('tidak_seimbang'); $after = $rows('setelah_cutoff')[0] ?? null; @endphp
<div class="card mg-card" id="jurnal">
  <div class="mg-h"><h2>Jurnal Khanza tidak seimbang</h2>@if($unb)<a href="{{ route('admin.closing.export', 'tidak-seimbang') }}" data-noload>Unduh (Excel)</a>@endif</div>
  @if($unb)
    <p class="mg-note">{{ count($unb) }} jurnal dengan total debet ≠ kredit, total selisih {{ F::rp(collect($unb)->sum(fn ($x) => $x['debet'] - $x['kredit'])) }}. Koreksi lewat jurnal penyesuaian.</p>
    <div class="table-scroll mg-scroll"><table class="mg-tbl cl-tbl"><thead><tr><th>No. jurnal</th><th>Tanggal</th><th>Keterangan</th><th class="r">Debet</th><th class="r">Kredit</th><th class="r">Selisih</th><th></th></tr></thead>
      <tbody>@foreach(array_slice($unb, 0, 200) as $x)<tr><td>{{ $x['no_jurnal'] }} <small>{{ $x['no_bukti'] }}</small></td><td>{{ $x['tgl_jurnal'] }}</td><td>{{ $x['keterangan'] }}</td>
        <td class="r">{{ F::n($x['debet'], 2) }}</td><td class="r">{{ F::n($x['kredit'], 2) }}</td><td class="r"><b>{{ F::n($x['debet'] - $x['kredit'], 2) }}</b></td>
        <td class="r">@if($edit)@php $d = round($x['debet'] - $x['kredit'], 2); @endphp<a class="btn sm" href="{{ route('admin.closing.adjustments.create', ['kind' => 'koreksi', 'date' => $x['tgl_jurnal'], 'desc' => 'Melengkapi jurnal Khanza '.$x['no_jurnal'].' yang tidak seimbang', 'lines' => [['coa' => '', $d > 0 ? 'credit' : 'debit' => abs($d), 'memo' => $x['no_jurnal'].' '.$x['no_bukti']]]]) }}">Lengkapi</a>@endif</td></tr>@endforeach</tbody></table></div>
  @else<p class="mg-empty">Semua jurnal Khanza pada periode ini seimbang.</p>@endif
</div>
@endif
@endsection
