@extends('layouts.admin', ['current' => 'closing'])
@use('App\Support\Fmt', 'F')
@section('title', 'Pemetaan akun · Closing')
@section('content')
@include('admin.closing._nav', ['actions' => $book->snap ? [['Unduh pemetaan (Excel)', route('admin.closing.export', 'pemetaan'), false]] : []])
@php $edit = $canEdit && !$book->locked(); @endphp

@if(!$book->snap)
  <div class="card mg-card"><p class="mg-empty">Ambil data dari Khanza terlebih dahulu di halaman Ringkasan.</p></div>
@else
<div class="stk-note">Setiap rekening Khanza yang punya saldo atau mutasi harus dipetakan ke <b>satu akun detail</b> COA baru. Beberapa rekening Khanza boleh ke akun COA yang sama.
  Ketik kode COA atau pilih dari daftar. Kolom saran diisi otomatis dari nama rekening, <b>periksa sebelum dipakai</b>.</div>

<div class="card" style="margin-bottom:16px"><form method="get" class="list-tools" data-noload>
  <div class="seg">
    @foreach(['aktif' => 'Bersaldo/bermutasi', 'belum' => 'Belum dipetakan', 'beda' => 'Perlu diperiksa', 'semua' => 'Semua rekening'] as $k => $label)
      <a href="{{ route('admin.closing.mapping', ['f' => $k, 'q' => $q ?: null]) }}" class="{{ $f === $k ? 'on' : '' }}">{{ $label }}</a>
    @endforeach
  </div>
  <input type="hidden" name="f" value="{{ $f }}"><input type="search" name="q" value="{{ $q }}" placeholder="Cari kode atau nama rekening">
  <span class="count">{{ $rows->count() }} rekening</span>
</form>
@if($edit && $suggestible)
  <form method="post" action="{{ route('admin.closing.mapping.suggest') }}" style="padding:0 16px 14px" data-confirm="{{ $suggestible }} rekening yang belum dipetakan akan diisi dengan saran otomatis. Anda tetap bisa mengubahnya.">@csrf
    <button class="btn sm">Terapkan {{ $suggestible }} saran untuk rekening yang belum dipetakan</button></form>
@endif
</div>

<datalist id="coaList">@foreach($book->coa->filter->isDetail() as $code => $a)<option value="{{ $code }}">{{ $code }} · {{ $a->name }} ({{ $a->category }})</option>@endforeach</datalist>
<script type="application/json" id="coaNames">{!! json_encode($book->coa->filter->isDetail()->map(fn ($a) => $a->name.' · '.$a->category), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

<form method="post" action="{{ route('admin.closing.mapping.save') }}" class="card" id="mapForm">
  @csrf
  <div class="table-scroll"><table class="tbl cl-tbl">
    <thead><tr><th>Rekening Khanza</th><th>Tipe</th><th class="r">Saldo awal</th><th class="r">Mutasi D</th><th class="r">Mutasi K</th><th class="r">Saldo cut off</th><th>Akun COA baru</th><th>Saran</th></tr></thead>
    <tbody>
    @forelse($rows as $kd => $r)
      <tr>
        <td><b>{{ $r['kd_rek'] }}</b> {{ $r['nm_rek'] ?: '—' }}
          @unless($r['terdaftar'])<span class="badge b-red" title="Dipakai jurnal tetapi tidak ada di tabel rekening Khanza">tidak terdaftar</span>@endunless</td>
        <td><span class="badge b-grey" title="{{ ['N' => 'Neraca', 'M' => 'Modal', 'R' => 'Rugi/laba'][$r['tipe']] ?? '' }}">{{ $r['tipe'] ?: '?' }}{{ $r['balance'] ? '/'.$r['balance'] : '' }}</span></td>
        <td class="r">{{ F::n($r['awal']) }}</td><td class="r">{{ F::n($r['debet']) }}</td><td class="r">{{ F::n($r['kredit']) }}</td>
        <td class="r"><b>{{ F::n(abs($r['akhir'])) }}</b> <small>{{ $r['akhir'] > 0 ? 'D' : ($r['akhir'] < 0 ? 'K' : '') }}</small></td>
        <td>
          <input class="coa" name="map[{{ $r['kd_rek'] }}]" value="{{ old('map.'.$r['kd_rek'], $r['coa_raw']) }}" list="coaList" autocomplete="off" aria-label="Akun COA untuk {{ $r['kd_rek'] }}" @disabled(!$edit)>
          <small class="sub coa-name" style="display:block;color:var(--muted)">{{ $r['coa'] ? $book->account($r['coa'])->name : '' }}</small>
          @if($r['coa_invalid'])<span class="badge b-red">kode tidak ada di COA</span>@endif
          @if($r['beda_tipe'])<span class="badge b-amber" title="Rekening {{ $r['tipe'] === 'R' ? 'laba rugi' : 'neraca' }} Khanza dipetakan ke akun {{ $book->account($r['coa'])->category }}">jenis berbeda</span>@endif
        </td>
        <td>@if($r['saran'] && $r['saran'] !== $r['coa'])<button type="button" class="btn sm" data-use="{{ $r['saran'] }}" @disabled(!$edit) title="{{ $book->account($r['saran'])?->name }}">{{ $r['saran'] }}</button>@endif</td>
      </tr>
    @empty
      <tr><td colspan="8" class="empty">Tidak ada rekening untuk filter ini.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  @if($edit && $rows->isNotEmpty())<div class="save-bar cl-sticky"><span id="mapDirty" style="margin-right:auto;color:var(--muted);font-size:13px"></span><button class="btn pri">Simpan pemetaan</button></div>@endif
</form>
@endif
@endsection

@push('scripts')
<script>
(function () {
  const f = document.getElementById("mapForm"); if (!f) return;
  const names = JSON.parse(document.getElementById("coaNames").textContent);
  const dirty = () => { const n = f.querySelectorAll("input.coa.chg").length; document.getElementById("mapDirty") && (document.getElementById("mapDirty").textContent = n ? n + " perubahan belum disimpan" : ""); };
  f.querySelectorAll("input.coa").forEach(i => {
    i.dataset.orig = i.value;
    i.addEventListener("input", () => { i.classList.toggle("chg", i.value !== i.dataset.orig); const n = i.parentElement.querySelector(".coa-name"); n.textContent = names[i.value.trim()] || (i.value.trim() ? "Kode tidak ada di COA" : ""); dirty(); });
  });
  f.addEventListener("click", e => { const b = e.target.closest("[data-use]"); if (!b) return; const i = b.closest("tr").querySelector("input.coa"); i.value = b.dataset.use; i.dispatchEvent(new Event("input")); });
  addEventListener("beforeunload", e => { if (f.querySelector("input.coa.chg") && !f.dataset.sending) { e.preventDefault(); e.returnValue = ""; } });
  f.addEventListener("submit", () => f.dataset.sending = "1");
})();
</script>
@endpush
