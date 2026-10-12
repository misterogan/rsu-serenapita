@extends('layouts.admin', ['current' => 'livechat'])
@section('title', 'Live chat')
@php $tab = request('tab', 'chat'); @endphp
@section('content')
<x-admin-head module="livechat" desc="Percakapan langsung dengan pengunjung situs. Pengunjung wajib mengisi nama dan nomor HP sebelum chat.">
  <div class="seg"><a href="?tab=chat" class="{{ $tab === 'chat' ? 'on' : '' }}">Percakapan</a><a href="?tab=settings" class="{{ $tab === 'settings' ? 'on' : '' }}">Pengaturan</a></div>
  <a class="btn" href="{{ route('home') }}" target="_blank" rel="noopener">Coba sebagai pengunjung</a>
</x-admin-head>
@if($tab === 'settings')
<form class="card" method="post" action="{{ route('admin.livechat.settings') }}">@csrf<div class="card-b form">
  <div class="fld"><div class="tog-row"><div><b>Aktifkan live chat</b><small>Jika dimatikan, tab Live chat di situs menampilkan nomor telepon.</small></div><label class="switch"><input type="checkbox" name="enabled" value="1" @checked($cfg['enabled'])><span></span></label></div></div>
  <div class="fld half"><label for="label">Nama petugas yang dilihat pengunjung</label><input id="label" name="label" value="{{ old('label', $cfg['label']) }}"></div>
  <div class="fld half"><label for="hours">Jam layanan live chat</label><input id="hours" name="hours" value="{{ old('hours', $cfg['hours']) }}"></div>
  <div class="fld"><label for="welcome">Pesan sambutan otomatis</label><textarea id="welcome" name="welcome" rows="2">{{ old('welcome', $cfg['welcome']) }}</textarea><div class="hint">Tulis {nama} untuk menyebut nama depan pengunjung.</div></div>
  <div class="fld"><label for="canned">Balasan cepat petugas</label><textarea id="canned" name="canned" rows="6">{{ old('canned', implode("\n", $cfg['canned'])) }}</textarea><div class="hint">Satu balasan per baris.</div></div>
</div><div class="save-bar"><button class="btn pri">Simpan pengaturan</button></div></form>
@else
<div class="lc" id="lc">
  <aside class="lc-list"><div class="lc-tools"><input type="search" id="lcSearch" placeholder="Cari nama atau nomor HP" aria-label="Cari percakapan"><div class="lc-tabs" id="lcTabs"></div></div><div class="lc-items" id="lcItems"><div class="empty">Memuat percakapan…</div></div></aside>
  <section class="lc-chat"><div class="lc-head" id="lcHead"></div><div class="lc-log" id="lcLog" aria-live="polite"></div><div id="lcComp"></div></section>
  <aside class="lc-info" id="lcInfo"></aside>
</div>
@push('scripts')
<script>
window.LC_CONFIG = {
  csrf: @json(csrf_token()),
  pollMs: @json(config('serenapita.livechat.agent_poll_ms')),
  canned: @json($cfg['canned']),
  routes: {
    feed: @json(route('admin.livechat.feed')),
    show: @json(route('admin.livechat.show', '__id__')),
    send: @json(route('admin.livechat.send', '__id__')),
    status: @json(route('admin.livechat.status', '__id__')),
    note: @json(route('admin.livechat.note', '__id__'))
  }
};
</script>
<script src="{{ asset('js/livechat-admin.js') }}?v=1"></script>
@endpush
@endif
@endsection
