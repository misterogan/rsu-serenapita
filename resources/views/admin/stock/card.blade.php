@extends('layouts.admin', ['current' => 'stock-report'])
@php use App\Models\StockEntry; use App\Support\Fmt; @endphp
@section('title', 'Kartu stok '.$medicine->name)
@section('content')
@php $month = $from->format('Y-m'); @endphp
<x-admin-head module="stock-report" :title="$medicine->name" :desc="'Kartu stok '.StockEntry::LOCATIONS[$loc].' bulan '.$from->translatedFormat('F Y').'. Golongan '.$medicine->group.', satuan '.$medicine->unit.'.'">
  <a class="btn" href="{{ route('admin.stock.report', ['unit' => $loc, 'month' => $month]) }}">← Kembali ke rekap</a>
  @if(auth()->user()->canModule('medicines'))<a class="btn" href="{{ route('admin.crud.edit', ['medicines', $medicine->id]) }}">Ubah data obat</a>@endif
</x-admin-head>

<div class="conn" id="tEnds">
  @foreach(StockEntry::LOCATIONS as $k => $n)
    <a class="card" href="{{ route('admin.stock.card', [$medicine, 'unit' => $k, 'month' => $month]) }}" style="text-decoration:none;color:inherit{{ $loc === $k ? ';border-color:var(--blue)' : '' }}"><small>Stok {{ $n }} saat ini</small><b>{{ Fmt::n($ends[$k]) }} {{ $medicine->unit }}</b></a>
  @endforeach
  <div class="card"><small>Harga per {{ $medicine->unit }}</small><b>{{ $medicine->price ? 'Rp '.Fmt::n($medicine->price, fmod($medicine->price, 1) ? 2 : 0) : '—' }}</b></div>
  <div class="card"><small>ED terdekat</small><b>{{ $medicine->expired_at?->translatedFormat('d M Y') ?? '—' }}</b></div>
</div>

<div class="card">
  <div class="card-h"><h2>Riwayat {{ $from->translatedFormat('F Y') }}</h2>
    <div class="vh-act">
      <a class="ibtn" href="{{ route('admin.stock.card', [$medicine, 'unit' => $loc, 'month' => $from->copy()->subMonth()->format('Y-m')]) }}" aria-label="Bulan sebelumnya"><svg class="i" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg></a>
      @if($from->copy()->addMonth()->lte(today()))<a class="ibtn" href="{{ route('admin.stock.card', [$medicine, 'unit' => $loc, 'month' => $from->copy()->addMonth()->format('Y-m')]) }}" aria-label="Bulan berikutnya"><svg class="i" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg></a>@endif
    </div>
  </div>
  <div class="table-scroll"><table class="tbl stk" id="tHist">
    <thead><tr><th>Tanggal</th><th class="n">Hasil SO</th><th class="n">Masuk</th><th class="n">Keluar</th><th class="n">Sisa</th><th>Catatan</th><th>Petugas</th></tr></thead>
    <tbody>
      <tr><td colspan="4"><i>Stok awal {{ $from->translatedFormat('d F Y') }}</i></td><td class="n"><b>{{ Fmt::n($start) }}</b></td><td colspan="2"></td></tr>
      @forelse($lines as $l)
        <tr>
          <td>@if(auth()->user()->canModule('stock'))<a href="{{ route('admin.stock', ['unit' => $loc, 'date' => $l->e->date->toDateString(), 'opname' => $l->e->counted !== null ? 1 : null]) }}" title="Buka lembar isian tanggal ini">{{ $l->e->date->translatedFormat('D, d M') }}</a>@else{{ $l->e->date->translatedFormat('D, d M') }}@endif</td>
          <td class="n">@if($l->e->counted !== null){{ $l->e->counted }}@if($l->e->counted !== $l->expected) <span class="sub {{ $l->e->counted < $l->expected ? 'neg' : '' }}">selisih {{ $l->e->counted > $l->expected ? '+' : '' }}{{ $l->e->counted - $l->expected }}</span>@endif @endif</td>
          <td class="n">{{ $l->e->qty_in ?: '' }}</td>
          <td class="n">{{ $l->e->qty_out ?: '' }}</td>
          <td class="n"><b class="{{ $l->balance < 0 ? 'neg' : '' }}">{{ Fmt::n($l->balance) }}</b></td>
          <td>{{ $l->e->note }}</td>
          <td><span class="sub">{{ $l->e->user?->name ?? '—' }}<br>{{ $l->e->updated_at->format('d/m H.i') }}</span></td>
        </tr>
      @empty
        <tr><td colspan="7"><div class="empty">Belum ada transaksi di bulan ini.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
</div>
@include('admin.tours.stock-card')
@endsection
