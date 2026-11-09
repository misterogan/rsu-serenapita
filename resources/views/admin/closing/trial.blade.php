@extends('layouts.admin', ['current' => 'closing'])
@use('App\Support\Fmt', 'F')
@section('title', 'Neraca saldo · Closing')
@section('content')
@include('admin.closing._nav', ['actions' => $book->snap ? [['Unduh neraca saldo (Excel)', route('admin.closing.export', 'neraca-saldo'), false]] : []])
@php
  $t = $tb['total']; $dk = fn ($v) => $v > 0 ? F::n($v, 2) : '';
  $rows = collect($tb['rows'])->filter(fn ($r) => $r['sumber'] || abs($r['akhir']) >= 0.01 || $r['adj_d'] || $r['adj_k'] || $r['code'] === '');
@endphp
@if(!$book->snap)
  <div class="card mg-card"><p class="mg-empty">Ambil data dari Khanza terlebih dahulu di halaman Ringkasan.</p></div>
@else
@if(abs($t['selisih']) >= 1)
  <div class="flash err">Neraca saldo tidak seimbang: selisih {{ F::rp($t['selisih']) }}@if(abs($t['selisih_khanza']) >= 1) (sudah ada di data Khanza: {{ F::rp($t['selisih_khanza']) }})@endif.
    Periksa saldo awal tahun di Khanza dan jurnal tidak seimbang di menu Rekonsiliasi, lalu lengkapi dengan jurnal koreksi.</div>
@endif
@if(isset($tb['rows']['']))
  <div class="flash err">Ada rekening Khanza yang belum dipetakan (baris "Belum dipetakan"). <a href="{{ route('admin.closing.mapping', ['f' => 'belum']) }}">Petakan sekarang</a>.</div>
@endif
<div class="card">
  <div class="list-tools"><div class="seg">
    <a href="{{ route('admin.closing.trial') }}" class="{{ $detail ? '' : 'on' }}">Per akun COA</a>
    <a href="{{ route('admin.closing.trial', ['rinci' => 1]) }}" class="{{ $detail ? 'on' : '' }}">Dengan rincian rekening Khanza</a>
  </div><span class="count">{{ $rows->count() }} akun bersaldo · saldo D−K: positif = debet</span></div>
  <div class="table-scroll"><table class="mg-tbl cl-tbl">
    <thead><tr><th>Akun</th><th class="r">Saldo awal</th><th class="r">Mutasi debet</th><th class="r">Mutasi kredit</th><th class="r">Khanza debet</th><th class="r">Khanza kredit</th>
      <th class="r">Penyesuaian D</th><th class="r">Penyesuaian K</th><th class="r">Akhir debet</th><th class="r">Akhir kredit</th></tr></thead>
    <tbody>
    @foreach($rows as $r)
      <tr @if($r['code'] === '') style="background:rgba(200,51,46,.06)" @endif>
        <td><b>{{ $r['code'] ?: '—' }}</b> {{ $r['name'] }} @if($r['category'])<small style="color:var(--muted)">· {{ $r['category'] }}</small>@endif</td>
        <td class="r {{ $r['awal'] < 0 ? 'neg' : '' }}">{{ $r['awal'] ? F::n($r['awal'], 2) : '' }}</td>
        <td class="r">{{ $r['debet'] ? F::n($r['debet'], 2) : '' }}</td><td class="r">{{ $r['kredit'] ? F::n($r['kredit'], 2) : '' }}</td>
        <td class="r">{{ $dk($r['khanza']) }}</td><td class="r">{{ $dk(-$r['khanza']) }}</td>
        <td class="r">{{ $r['adj_d'] ? F::n($r['adj_d'], 2) : '' }}</td><td class="r">{{ $r['adj_k'] ? F::n($r['adj_k'], 2) : '' }}</td>
        <td class="r"><b>{{ $dk($r['akhir']) }}</b></td><td class="r"><b>{{ $dk(-$r['akhir']) }}</b></td>
      </tr>
      @if($detail)
        @foreach($r['sumber'] as $kd)@php $k = $kh[$kd]; @endphp
          <tr class="src"><td style="padding-left:28px">↳ {{ $k['kd_rek'] }} {{ $k['nm_rek'] }}</td><td class="r">{{ $k['awal'] ? F::n($k['awal'], 2) : '' }}</td>
            <td class="r">{{ $k['debet'] ? F::n($k['debet'], 2) : '' }}</td><td class="r">{{ $k['kredit'] ? F::n($k['kredit'], 2) : '' }}</td>
            <td class="r">{{ $dk($k['akhir']) }}</td><td class="r">{{ $dk(-$k['akhir']) }}</td><td colspan="4"></td></tr>
        @endforeach
      @endif
    @endforeach
    <tr class="tot"><td>Total</td><td class="r">{{ F::n($t['awal'], 2) }}</td><td class="r">{{ F::n($t['debet'], 2) }}</td><td class="r">{{ F::n($t['kredit'], 2) }}</td>
      <td class="r">{{ F::n($t['khanza_d'], 2) }}</td><td class="r">{{ F::n($t['khanza_k'], 2) }}</td><td class="r">{{ F::n($t['adj_d'], 2) }}</td><td class="r">{{ F::n($t['adj_k'], 2) }}</td>
      <td class="r">{{ F::n($t['akhir_d'], 2) }}</td><td class="r">{{ F::n($t['akhir_k'], 2) }}</td></tr>
    </tbody>
  </table></div>
</div>
@endif
@endsection
