@extends('layouts.admin', ['current' => 'stock-report'])
@php use App\Models\StockEntry; use App\Support\Fmt; use App\Support\StockLedger; @endphp
@section('title', 'Rekap persediaan')
@section('content')
@php
  $month = $from->format('Y-m');
  $count = fn ($label) => $rows->filter(fn ($r) => $r->status[0] === $label)->count();
  $edSoon = $rows->filter(fn ($r) => $r->m->expiryState())->count();
  $value = $rows->sum(fn ($r) => max(0, $r->value ?? 0));
  $groups = $rows->pluck('m.group')->unique()->values();
  $neverCounted = $rows->whereNull('counted_at')->count();
@endphp
<x-admin-head module="stock-report" :desc="'Ringkasan stok '.StockEntry::LOCATIONS[$loc].' periode '.$from->translatedFormat('d M').' – '.$to->translatedFormat('d M Y').'. Dihitung otomatis dari Input stok harian, tidak perlu rumus Excel lagi.'">
  <a class="btn" id="tExport" href="{{ route('admin.stock.export', ['unit' => $loc, 'month' => $month]) }}" data-noload><svg class="i" viewBox="0 0 24 24"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>Unduh Excel (CSV)</a>
  @if(auth()->user()->canModule('stock'))<a class="btn pri" href="{{ route('admin.stock', ['unit' => $loc]) }}">Isi stok hari ini</a>@endif
</x-admin-head>

<div class="card stk-bar">
  <div class="seg" id="tUnit" role="group" aria-label="Unit">
    @foreach(StockEntry::LOCATIONS as $k => $n)<a href="{{ route('admin.stock.report', ['unit' => $k, 'month' => $month]) }}" class="{{ $loc === $k ? 'on' : '' }}">{{ $n }}</a>@endforeach
  </div>
  <form class="stk-date" method="get" action="{{ route('admin.stock.report') }}">
    <input type="hidden" name="unit" value="{{ $loc }}">
    <a class="ibtn" href="{{ route('admin.stock.report', ['unit' => $loc, 'month' => $from->copy()->subMonth()->format('Y-m')]) }}" title="Bulan sebelumnya" aria-label="Bulan sebelumnya"><svg class="i" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg></a>
    <input type="month" name="month" value="{{ $month }}" max="{{ today()->format('Y-m') }}" aria-label="Bulan" onchange="this.form.submit()">
    @if($from->copy()->addMonth()->lte(today()))<a class="ibtn" href="{{ route('admin.stock.report', ['unit' => $loc, 'month' => $from->copy()->addMonth()->format('Y-m')]) }}" title="Bulan berikutnya" aria-label="Bulan berikutnya"><svg class="i" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg></a>@endif
    <span class="stk-day">{{ $from->translatedFormat('F Y') }}</span>
  </form>
</div>

<div class="stats" id="tStats">
  <button type="button" class="stat s4" data-status="Perlu pesan"><span>Perlu dipesan</span><b>{{ $count('Perlu pesan') }}</b><small>Stok di bawah titik pesan ulang</small></button>
  <button type="button" class="stat s1" data-status="Habis"><span>Habis / minus</span><b>{{ $count('Habis') + $count('Minus') }}</b><small>{{ $count('Minus') ? $count('Minus').' minus, periksa isian' : 'Stok 0' }}</small></button>
  <button type="button" class="stat s2" data-status="ed"><span>ED ≤ {{ App\Models\Medicine::ED_WARN_DAYS }} hari</span><b>{{ $edSoon }}</b><small>Termasuk yang sudah kedaluwarsa</small></button>
  <div class="stat s3"><span>Nilai stok akhir</span><b>{{ Fmt::rps($value) }}</b><small>{{ $rows->count() }} obat aktif</small></div>
</div>
@if($neverCounted === $rows->count())
  <div class="sync-note" style="margin-bottom:20px"><svg class="i" viewBox="0 0 24 24"><path d="M12 9v4M12 17h.01M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg><div><b>Stok awal belum diisi.</b><span>Lakukan stock opname sekali di awal (menu Input stok harian → Stock opname) supaya stok sistem sama dengan stok fisik. Setelah itu cukup isi masuk dan keluar setiap hari.</span></div></div>
@endif

<div class="card">
  <div class="list-tools">
    <input type="search" id="rpQ" placeholder="Cari nama atau kode obat…" aria-label="Cari nama obat" autocomplete="off">
    <select id="rpG" aria-label="Golongan"><option value="">Semua golongan</option>@foreach($groups as $g)<option>{{ $g }}</option>@endforeach</select>
    <select id="rpS" aria-label="Status"><option value="">Semua status</option><option>Perlu pesan</option><option>Habis</option><option>Minus</option><option>Aman</option><option value="ed">ED dekat / lewat</option><option value="moved">Ada transaksi bulan ini</option></select>
    <span class="count" id="rpCount">{{ $rows->count() }} obat</span>
  </div>
  <div class="table-scroll stk-scroll">
    <table class="tbl stk rp" id="rpTbl">
      <thead><tr>
        <th>Nama obat</th><th class="n">Stok awal</th><th class="n">Masuk</th><th class="n">Keluar</th><th class="n" title="Selisih stock opname (hasil hitung − stok sistem)">Selisih SO</th><th class="n">Stok akhir</th>
        <th class="n" title="Rata-rata keluar per hari">Pakai/hari</th><th class="n" title="Safety stock = rata-rata × {{ StockLedger::SAFETY_DAYS }} hari">Safety</th><th class="n" title="Titik pesan ulang = rata-rata × {{ StockLedger::ROP_DAYS }} hari">ROP</th><th>Status</th><th class="n">Nilai stok</th><th>ED</th>
      </tr></thead>
      <tbody>
      @foreach($rows as $r)
        @php $ed = $r->m->expiryState(); @endphp
        <tr class="rp-row" data-group="{{ $r->m->group }}" data-name="{{ mb_strtolower($r->m->name.' '.$r->m->code) }}" data-status="{{ $r->status[0] }}" data-ed="{{ $ed ? 1 : 0 }}" data-moved="{{ $r->in || $r->out || $r->adjust ? 1 : 0 }}">
          <td class="nm"><a class="main" href="{{ route('admin.stock.card', [$r->m, 'unit' => $loc, 'month' => $month]) }}">{{ $r->m->name }}</a><span class="sub">{{ $r->m->code ? $r->m->code.' · ' : '' }}{{ $r->m->group }} · {{ $r->m->unit }}@if($r->counted_at) · SO {{ $r->counted_at->format('d/m') }}@endif</span></td>
          <td class="n">{{ Fmt::n($r->start) }}</td>
          <td class="n">{{ $r->in ? Fmt::n($r->in) : '' }}</td>
          <td class="n">{{ $r->out ? Fmt::n($r->out) : '' }}</td>
          <td class="n {{ $r->adjust < 0 ? 'neg' : '' }}">{{ $r->adjust ? ($r->adjust > 0 ? '+' : '').Fmt::n($r->adjust) : '' }}</td>
          <td class="n"><b>{{ Fmt::n($r->end) }}</b></td>
          <td class="n">{{ $r->avg ? Fmt::n($r->avg, 1) : '' }}</td>
          <td class="n">{{ $r->safety ?: '' }}</td>
          <td class="n">{{ $r->rop ?: '' }}</td>
          <td><span class="badge b-{{ $r->status[1] }}">{{ $r->status[0] }}</span></td>
          <td class="n">{{ $r->value !== null ? Fmt::n($r->value) : '—' }}</td>
          <td>@if($r->m->expired_at)<span class="badge b-{{ match ($ed) { 'expired' => 'red', 'soon' => 'amber', default => 'grey' } }}">{{ $r->m->expired_at->translatedFormat('M Y') }}</span>@endif</td>
        </tr>
      @endforeach
      </tbody>
    </table>
    <div class="empty" id="rpEmpty" hidden>Tidak ada obat yang cocok dengan filter.</div>
  </div>
</div>
@endsection
@include('admin.tours.stock-report')
@push('scripts')
<script>
(function () {
  const $ = s => document.querySelector(s), rows = [...document.querySelectorAll(".rp-row")];
  const q = $("#rpQ"), g = $("#rpG"), st = $("#rpS");
  const apply = () => {
    const words = q.value.toLowerCase().trim().split(/\s+/).filter(Boolean); let n = 0;
    rows.forEach(tr => {
      const s = st.value, ok = (!g.value || tr.dataset.group === g.value) && words.every(w => tr.dataset.name.includes(w))
        && (!s || (s === "ed" ? tr.dataset.ed === "1" : s === "moved" ? tr.dataset.moved === "1" : s === "Habis" ? ["Habis", "Minus"].includes(tr.dataset.status) : tr.dataset.status === s));
      tr.hidden = !ok; n += ok;
    });
    $("#rpCount").textContent = n + " obat"; $("#rpEmpty").hidden = n > 0;
  };
  [q, g, st].forEach(el => el.addEventListener("input", apply));
  document.querySelectorAll(".stat[data-status]").forEach(b => b.addEventListener("click", () => {
    st.value = st.value === b.dataset.status ? "" : b.dataset.status; apply(); $("#rpTbl").scrollIntoView({ behavior: "smooth", block: "start" });
  }));
})();
</script>
@endpush
