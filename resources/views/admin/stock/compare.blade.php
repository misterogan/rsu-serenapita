@extends('layouts.admin', ['current' => 'stock-compare'])
@php use App\Models\Medicine; use App\Models\StockEntry; use App\Support\Fmt; use App\Support\StockCompare; @endphp
@section('title', 'Cocokkan SIMRS')
@section('content')
@php
  $by = collect($rows)->groupBy('status');
  $renamed = collect($rows)->where('renamed', true)->count();
  $newCount = collect($rows)->filter(fn ($r) => StockCompare::addable($r))->count();
@endphp
<x-admin-head module="stock-compare" desc="Unggah file ekspor stok dari SIMRS, lalu sistem membandingkannya per kode obat dengan stok di sini. Dipakai selama masa pengisian paralel untuk memastikan kedua data sama.">
  @if($data)
    <a class="btn" id="tCsv" href="{{ route('admin.stock.compare.export') }}" data-noload><svg class="i" viewBox="0 0 24 24"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>Unduh hasil (CSV)</a>
  @endif
</x-admin-head>

<form class="card" id="tUpload" method="post" action="{{ route('admin.stock.compare.upload') }}" enctype="multipart/form-data" style="margin-bottom:20px">
  @csrf
  <div class="card-b form">
    <div class="fld"><label for="cmpFile">File ekspor stok SIMRS (.xlsx atau .csv)</label>
      <input id="cmpFile" type="file" name="file" accept=".xlsx,.csv" required>
      <div class="hint">Kolom yang dibaca: <i>obatalkes_kode</i>, <i>obatalkes_nama</i>, <i>satuankecil</i>, <i>stok_saatini</i>. Ekspor dari SIMRS per unit.</div></div>
    <div class="fld half"><label for="cmpUnit">Unit di file ini</label>
      <select id="cmpUnit" name="unit">@foreach(auth()->user()->stockLocations() as $k => $n)<option value="{{ $k }}" @selected(old('unit', $data['unit'] ?? session('stock.unit')) === $k)>{{ $n }}</option>@endforeach</select></div>
    <div class="fld half"><label for="cmpDate">Bandingkan dengan stok sistem per tanggal</label>
      <input id="cmpDate" type="date" name="date" value="{{ old('date', $data['date'] ?? today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
      <div class="hint">Samakan dengan tanggal file SIMRS diunduh. Stok sistem dihitung sampai akhir tanggal ini.</div></div>
    <div class="fld half"><label for="cmpSheet">Nama sheet (opsional)</label>
      <input id="cmpSheet" type="text" name="sheet" value="{{ old('sheet') }}" placeholder="Kosongkan: otomatis">
      <div class="hint">Bila file berisi beberapa sheet, otomatis dipakai sheet yang paling banyak berisi angka stok.</div></div>
  </div>
  <div class="save-bar"><button class="btn pri">Unggah dan cocokkan</button></div>
</form>

@if($data)
  <div class="stk-note" id="tInfo">
    <b>{{ $data['file'] }}</b> (sheet <i>{{ $data['sheet'] }}</i>@if(count($data['sheets']) > 1), sheet lain: {{ implode(', ', array_diff($data['sheets'], [$data['sheet']])) }}@endif)
    dibandingkan dengan stok <b>{{ StockEntry::LOCATIONS[$data['unit']] }}</b> di sistem per <b>{{ Carbon\Carbon::parse($data['date'])->translatedFormat('d F Y') }}</b>. Diunggah {{ $data['at'] }}.
    <form method="post" action="{{ route('admin.stock.compare.clear') }}" class="inline">@csrf<button class="btn sm" style="margin-left:6px">Hapus hasil</button></form>
  </div>

  <div class="stats" id="tStats">
    <button type="button" class="stat s1" data-status="selisih"><span>Selisih</span><b>{{ $by->get('selisih', collect())->count() }}</b><small>Stok sistem ≠ stok SIMRS</small></button>
    <button type="button" class="stat s4" data-status="cocok"><span>Cocok</span><b>{{ $by->get('cocok', collect())->count() }}</b><small>Termasuk yang sama-sama 0</small></button>
    <button type="button" class="stat s2" data-status="beda-daftar"><span>Beda daftar</span><b>{{ $by->get('baru', collect())->count() + $by->get('hilang', collect())->count() }}</b><small>{{ $by->get('baru', collect())->count() }} belum ada di sistem · {{ $by->get('hilang', collect())->count() }} tidak ada di SIMRS</small></button>
    <button type="button" class="stat s3" data-status="renamed"><span>Nama berubah di SIMRS</span><b>{{ $renamed }}</b><small>Kode sama, nama berbeda</small></button>
  </div>

  @if($renamed || $newCount)
  <div class="card" id="tActs" style="margin-bottom:20px"><div class="card-b" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    @if($renamed)<form method="post" action="{{ route('admin.stock.compare.rename') }}" class="inline" data-confirm="{{ $renamed }} nama barang di sistem akan diganti sesuai nama di SIMRS. Stok dan riwayatnya tidak berubah.">@csrf<button class="btn">Samakan {{ $renamed }} nama dengan SIMRS</button></form>@endif
    @if($newCount)<form method="post" action="{{ route('admin.stock.compare.add') }}" class="inline" data-confirm="{{ $newCount }} kode SIMRS yang belum ada akan ditambahkan sebagai barang baru dengan stok 0 (tersembunyi sampai diisi).">@csrf<button class="btn">Tambahkan {{ $newCount }} kode baru dari SIMRS</button></form>@endif
    <span class="sub" style="color:var(--muted);font-size:13px">Tindakan ini hanya mengubah daftar barang, tidak mengubah angka stok.</span>
  </div></div>
  @endif

  <div class="card">
    <div class="list-tools">
      <input type="search" id="cmpQ" placeholder="Cari nama atau kode…" aria-label="Cari nama atau kode" autocomplete="off">
      <select id="cmpS" aria-label="Status">
        <option value="beda">Hanya yang berbeda</option><option value="">Semua</option>
        @foreach(StockCompare::STATUS as $k => [$label])<option value="{{ $k }}">{{ $label }}</option>@endforeach
        <option value="renamed">Nama berubah di SIMRS</option><option value="beda-daftar">Beda daftar</option>
      </select>
      <span class="count" id="cmpCount"></span>
    </div>
    <div class="table-scroll stk-scroll">
      <table class="tbl stk" id="cmpTbl">
        <thead><tr><th>Kode / nama</th><th>Jenis</th><th class="n">Stok SIMRS</th><th class="n">Stok sistem</th><th class="n" title="Stok sistem dikurangi stok SIMRS">Selisih</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($rows as $r)
          <tr class="cmp-row" data-status="{{ $r->status }}" data-renamed="{{ $r->renamed ? 1 : 0 }}" data-name="{{ mb_strtolower($r->code.' '.$r->simrsName.' '.$r->med?->name) }}">
            <td class="nm">
              @if($r->med && auth()->user()->canModule('stock-report'))<a class="main" href="{{ route('admin.stock.card', [$r->med, 'unit' => $data['unit']]) }}">{{ $r->simrsName ?? $r->med->name }}</a>@else<span class="main">{{ $r->simrsName ?? $r->med?->name }}</span>@endif
              <span class="sub">{{ $r->code ?: 'tanpa kode' }}@if($r->renamed) · <span class="ed-soon" title="Nama di sistem saat ini">di sistem: {{ $r->med->name }}</span>@endif</span>
            </td>
            <td>{{ $r->med ? Medicine::KINDS[$r->med->kind] : '—' }}</td>
            <td class="n">{{ $r->simrs !== null ? Fmt::n($r->simrs) : '—' }}</td>
            <td class="n">{{ $r->system !== null ? Fmt::n($r->system) : '—' }}</td>
            <td class="n"><b class="{{ $r->diff ? ($r->diff < 0 ? 'neg' : 'end low') : '' }}">{{ $r->diff ? ($r->diff > 0 ? '+' : '').Fmt::n($r->diff) : ($r->diff === 0 ? '0' : '—') }}</b></td>
            <td><span class="badge b-{{ StockCompare::STATUS[$r->status][1] }}">{{ StockCompare::STATUS[$r->status][0] }}</span></td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <div class="empty" id="cmpEmpty" hidden>Tidak ada baris yang cocok dengan filter. Bila filter <i>Hanya yang berbeda</i> kosong, berarti semua stok sudah sama dengan SIMRS.</div>
    </div>
  </div>
@else
  <div class="card"><div class="empty">Belum ada file yang dicocokkan. Unggah file ekspor stok SIMRS di atas.</div></div>
@endif
@include('admin.tours.stock-compare')
@endsection
@push('scripts')
<script>
(function () {
  const $ = s => document.querySelector(s), rows = [...document.querySelectorAll(".cmp-row")]; if (!rows.length) return;
  const q = $("#cmpQ"), st = $("#cmpS");
  const match = (tr, s) => s === "" ? true : s === "beda" ? tr.dataset.status !== "cocok" || tr.dataset.renamed === "1"
    : s === "renamed" ? tr.dataset.renamed === "1" : s === "beda-daftar" ? ["baru", "hilang"].includes(tr.dataset.status) : tr.dataset.status === s;
  const apply = () => {
    const words = q.value.toLowerCase().trim().split(/\s+/).filter(Boolean); let n = 0;
    rows.forEach(tr => { const ok = match(tr, st.value) && words.every(w => tr.dataset.name.includes(w)); tr.hidden = !ok; n += ok; });
    $("#cmpCount").textContent = n + " dari " + rows.length + " baris"; $("#cmpEmpty").hidden = n > 0;
  };
  [q, st].forEach(el => el.addEventListener("input", apply));
  document.querySelectorAll(".stat[data-status]").forEach(b => b.addEventListener("click", () => { st.value = b.dataset.status; apply(); $("#cmpTbl").scrollIntoView({ behavior: "smooth", block: "start" }); }));
  apply();
})();
</script>
@endpush
