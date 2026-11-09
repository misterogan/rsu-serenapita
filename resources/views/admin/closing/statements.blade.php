@extends('layouts.admin', ['current' => 'closing'])
@use('App\Support\Fmt', 'F')
@section('title', 'Laporan keuangan · Closing')
@section('content')
@include('admin.closing._nav', ['actions' => $book->snap ? [['Neraca', route('admin.closing.export', 'neraca'), false], ['Laba rugi', route('admin.closing.export', 'laba-rugi'), false], ['Laba rugi per bulan', route('admin.closing.export', 'laba-rugi-bulanan'), false]] : []])
@php $cut = \Illuminate\Support\Carbon::parse($book->settings['cutoff'])->translatedFormat('d F Y'); @endphp
@if(!$book->snap)
  <div class="card mg-card"><p class="mg-empty">Ambil data dari Khanza terlebih dahulu di halaman Ringkasan.</p></div>
@else
<p class="mg-note">Setelah jurnal penyesuaian. Laporan ini hanya berisi data Khanza sampai cut off; transaksi setelah cut off ada di SIMRS baru.</p>
@php
  $show = fn ($lines) => collect($lines)->filter(fn ($l) => $l['type'] !== 'detail' || abs($l['amount']) >= 0.01);
@endphp
<div class="mg-grid">
  <div class="card mg-card">
    <div class="mg-h"><h2>Neraca per {{ $cut }}</h2></div>
    <table class="mg-tbl cl-tbl"><tbody>
    @foreach($show($neraca) as $l)
      <tr class="{{ $l['type'] === 'header' ? 'hd' : ($l['type'] === 'sub' ? 'sb' : '') }}"><td style="padding-left:{{ 8 + $l['depth'] * 16 }}px">{{ $l['type'] === 'detail' ? $l['code'].' ' : '' }}{{ $l['name'] }}</td>
        <td class="r {{ $l['amount'] < 0 ? 'neg' : '' }}">{{ F::n($l['amount'], 2) }}</td></tr>
    @endforeach
    <tr class="sb"><td>Laba (rugi) tahun berjalan s.d. cut off</td><td class="r">{{ F::n($summary['laba'], 2) }}</td></tr>
    <tr class="tot"><td>Total kewajiban + ekuitas + laba berjalan</td><td class="r">{{ F::n($summary['kewajiban'] + $summary['ekuitas'] + $summary['laba'], 2) }}</td></tr>
    @if(abs($summary['selisih']) >= 1)<tr><td><span class="tag bad">Selisih neraca</span></td><td class="r neg">{{ F::n($summary['selisih'], 2) }}</td></tr>@endif
    </tbody></table>
  </div>
  <div class="card mg-card">
    <div class="mg-h"><h2>Laba rugi s.d. {{ $cut }}</h2></div>
    <table class="mg-tbl cl-tbl"><tbody>
    @foreach($show($lr) as $l)
      <tr class="{{ $l['type'] === 'header' ? 'hd' : ($l['type'] === 'sub' ? 'sb' : '') }}"><td style="padding-left:{{ 8 + $l['depth'] * 16 }}px">{{ $l['type'] === 'detail' ? $l['code'].' ' : '' }}{{ $l['name'] }}</td>
        <td class="r {{ $l['amount'] < 0 ? 'neg' : '' }}">{{ F::n($l['amount'], 2) }}</td></tr>
    @endforeach
    <tr class="tot"><td>Laba (rugi) bersih</td><td class="r {{ $summary['laba'] < 0 ? 'neg' : '' }}">{{ F::n($summary['laba'], 2) }}</td></tr>
    </tbody></table>
  </div>
</div>

<div class="card mg-card">
  <div class="mg-h"><h2>Laba rugi per bulan</h2></div>
  <p class="mg-note">Untuk digabung dengan laporan SIMRS baru saat menyusun laporan setahun penuh. Nilai normal: pendapatan dan beban sama-sama positif.</p>
  <div class="table-scroll"><table class="mg-tbl cl-tbl">
    <thead><tr><th>Akun</th>@foreach($months as $m)<th class="r">{{ F::month($m) }}</th>@endforeach<th class="r">Total</th></tr></thead>
    <tbody>
    @php $net = array_fill_keys($months, 0.0); @endphp
    @foreach($book->coa as $code => $a)
      @continue(!$a->isDetail() || !isset($monthly[$code]))
      @php foreach ($months as $m) { $net[$m] += ($a->category === 'Pendapatan' ? 1 : -1) * ($monthly[$code][$m] ?? 0); } @endphp
      <tr><td>{{ $code }} {{ $a->name }}</td>@foreach($months as $m)<td class="r">{{ isset($monthly[$code][$m]) ? F::n($monthly[$code][$m]) : '' }}</td>@endforeach<td class="r"><b>{{ F::n(array_sum($monthly[$code])) }}</b></td></tr>
    @endforeach
    <tr class="tot"><td>Laba (rugi) bersih</td>@foreach($months as $m)<td class="r {{ $net[$m] < 0 ? 'neg' : '' }}">{{ F::n($net[$m]) }}</td>@endforeach<td class="r">{{ F::n(array_sum($net)) }}</td></tr>
    </tbody>
  </table></div>
</div>
@endif
@endsection
