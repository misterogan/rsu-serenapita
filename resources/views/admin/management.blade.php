@extends('layouts.admin', ['current' => 'management'])
@section('title', 'Dasbor manajemen')
@section('content')
<x-admin-head module="management" :desc="($ops && $fin ? 'Pelayanan, rawat inap, penunjang, keuangan, dan klaim BPJS' : ($fin ? 'Pembayaran, piutang, pendapatan, biaya, dan klaim BPJS' : 'Pelayanan, rawat inap, penunjang, dan farmasi')).' langsung dari database SIMRS Khanza. Data hanya dibaca, tidak diubah.'">
  @if($configured)<a class="btn" href="{{ route('admin.management', $pq + ['segarkan' => 1]) }}" data-keep-hash>Muat ulang data</a>@endif
</x-admin-head>

<form class="card mg-period" method="get" data-noload>
  <div class="mg-chips" role="group" aria-label="Periode">
    @foreach($presets as $k => $label)<a class="mg-chip {{ $preset === $k ? 'on' : '' }}" href="{{ route('admin.management', ['periode' => $k]) }}" data-keep-hash>{{ $label }}</a>@endforeach
  </div>
  <div class="mg-custom">
    <input type="hidden" name="periode" value="custom">
    <label>Dari <input type="date" name="dari" value="{{ $from }}"></label>
    <label>Sampai <input type="date" name="sampai" value="{{ $to }}"></label>
    <button class="btn sm">Terapkan</button>
  </div>
  <div class="mg-meta">Periode {{ \Illuminate\Support\Carbon::parse($from)->translatedFormat('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->translatedFormat('d M Y') }} · data disimpan sementara {{ config('serenapita.khanza.cache_minutes') }} menit</div>
</form>

@if(!$configured)
  <div class="card" style="padding:22px 24px">
    <h2 style="margin-top:0">Hubungkan ke database Khanza</h2>
    <p>Dasbor ini membaca langsung database SIMRS Khanza. Minta tim IT menambahkan pengaturan koneksi ke file <code>.env</code> server, lalu menjalankan <code>php artisan config:clear</code>. Langkah lengkapnya ada di file <code>database/khanza/README.txt</code> pada proyek website.</p>
  </div>
@elseif($connError)
  <div class="flash err">Tidak bisa terhubung ke database Khanza: {{ $connError }}</div>
@else
  <nav class="mg-tabs" role="tablist" aria-label="Bagian dasbor">
    @foreach($tabs as $k => [$label])<button type="button" role="tab" id="t-{{ $k }}" aria-controls="p-{{ $k }}" data-tab="{{ $k }}" aria-selected="false">{{ $label }}</button>@endforeach
  </nav>
  @foreach($tabs as $k => [$label])
    <section class="mg-panel" id="p-{{ $k }}" role="tabpanel" aria-labelledby="t-{{ $k }}" hidden
      data-src="{{ route('admin.management.tab', [$k] + $pq + (!empty($fresh) ? ['segarkan' => 1] : [])) }}"></section>
  @endforeach
  <dialog class="mg-dialog" id="mgDialog"><div class="mg-dialog-b"></div><form method="dialog"><button class="btn sm">Tutup</button></form></dialog>
@endif
@endsection

@push('scripts')
<script>
(function () {
  const tabs = [...document.querySelectorAll(".mg-tabs [data-tab]")]; if (!tabs.length) return;
  const keys = tabs.map(t => t.dataset.tab);
  const load = async (panel) => {
    if (panel.dataset.loaded) return;
    panel.dataset.loaded = "1";
    panel.innerHTML = '<div class="mg-loading"><span class="mg-spin"></span>Mengambil data dari SIMRS Khanza…</div>';
    try {
      const r = await fetch(panel.dataset.src, { headers: { "X-Requested-With": "XMLHttpRequest" } });
      if (!r.ok) throw new Error(r.status);
      panel.innerHTML = await r.text();
    } catch (e) {
      panel.dataset.loaded = "";
      panel.innerHTML = '<div class="flash err">Bagian ini gagal dimuat. <button type="button" class="btn sm" data-retry>Coba lagi</button></div>';
    }
  };
  const show = (k, push) => {
    if (!keys.includes(k)) k = keys[0];
    tabs.forEach(t => t.setAttribute("aria-selected", t.dataset.tab === k));
    document.querySelectorAll(".mg-panel").forEach(p => p.hidden = p.id !== "p-" + k);
    const panel = document.getElementById("p-" + k); load(panel);
    if (push) history.replaceState(null, "", "#" + k);
  };
  tabs.forEach(t => t.addEventListener("click", () => show(t.dataset.tab, true)));
  document.addEventListener("click", async e => {
    const retry = e.target.closest("[data-retry]");
    if (retry) { const p = retry.closest(".mg-panel"); load(p); return; }
    const keep = e.target.closest("a[data-keep-hash]");
    if (keep && location.hash) { keep.href = keep.href.split("#")[0] + location.hash; }
    const poli = e.target.closest("[data-poli-url]");
    if (poli) {
      e.preventDefault();
      const dlg = document.getElementById("mgDialog"), body = dlg.querySelector(".mg-dialog-b");
      body.innerHTML = '<div class="mg-loading"><span class="mg-spin"></span>Memuat…</div>'; dlg.showModal();
      try { const r = await fetch(poli.dataset.poliUrl); body.innerHTML = await r.text(); } catch (x) { body.textContent = "Gagal memuat rincian."; }
    }
  });
  document.querySelector(".mg-period")?.addEventListener("submit", e => { if (location.hash) e.target.action = location.pathname + location.hash; });
  window.addEventListener("hashchange", () => show(location.hash.slice(1), false));
  show((location.hash || "").slice(1), false);
})();
</script>
@endpush
