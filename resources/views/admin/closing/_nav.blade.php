@php
  $s = $book->settings;
  $routes = ['index' => 'admin.closing', 'coa' => 'admin.closing.coa', 'mapping' => 'admin.closing.mapping', 'trial' => 'admin.closing.trial',
    'statements' => 'admin.closing.statements', 'reconcile' => 'admin.closing.reconcile', 'carryover' => 'admin.closing.carryover', 'adjustments' => 'admin.closing.adjustments'];
  $active = in_array($section, ['adjustment-form']) ? 'adjustments' : $section;
@endphp
<x-admin-head module="closing" :title="$title ?? null" :desc="$desc ?? 'Merapikan data SIMRS Khanza sampai tanggal cut off untuk tutup buku, memetakannya ke COA baru, dan menyiapkan saldo awal SIMRS baru.'">
  @foreach($actions ?? [] as [$label, $href, $pri])<a class="btn {{ $pri ? 'pri' : '' }}" href="{{ $href }}" data-noload><svg class="i" viewBox="0 0 24 24"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>{{ $label }}</a>@endforeach
</x-admin-head>
<div class="cl-status">
  <span class="badge b-blue">Cut off {{ \Illuminate\Support\Carbon::parse($s['cutoff'])->translatedFormat('d M Y') }} · SIMRS baru mulai {{ \Illuminate\Support\Carbon::parse($s['cutoff'])->addDay()->translatedFormat('d M Y') }}</span>
  <span class="badge b-grey">Tahun buku mulai {{ \Illuminate\Support\Carbon::parse($s['year_start'])->translatedFormat('d M Y') }}</span>
  @if($book->snap)
    <span class="badge {{ $book->snapshotCurrent() ? 'b-green' : 'b-amber' }}">Data Khanza {{ $book->snap->created_at->translatedFormat('d M Y H:i') }}{{ $book->snapshotCurrent() ? '' : ' · perlu diambil ulang' }}</span>
  @else
    <span class="badge b-red">Data Khanza belum diambil</span>
  @endif
  @if($book->locked())<span class="badge b-green">Dikunci</span>@endif
  @unless($canEdit)<span class="badge b-grey">Hanya lihat</span>@endunless
  <a href="{{ route('admin.closing.guide') }}#{{ ['index' => 'cl-ringkasan', 'coa' => 'cl-coa', 'mapping' => 'cl-pemetaan', 'trial' => 'cl-neraca-saldo', 'statements' => 'cl-laporan', 'reconcile' => 'cl-rekonsiliasi', 'carryover' => 'cl-ranap', 'adjustments' => 'cl-penyesuaian'][$active] ?? 'cl-pengenalan' }}" style="margin-left:auto;font-weight:700">Panduan halaman ini</a>
</div>
<nav class="cl-tabs" aria-label="Bagian closing">
  @foreach($sections as $k => $label)<a href="{{ route($routes[$k]) }}" class="{{ $active === $k ? 'on' : '' }}" @if($active === $k) aria-current="page" @endif>{{ $label }}</a>@endforeach
</nav>
