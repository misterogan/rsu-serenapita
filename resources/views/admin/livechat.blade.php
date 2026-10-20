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
  <div class="fld"><div class="tog-row"><div><b>Aktifkan live chat</b><small>Jika dimatikan, tombol Chat dengan petugas disembunyikan dan pengunjung hanya dilayani asisten.</small></div><label class="switch"><input type="checkbox" name="enabled" value="1" @checked($cfg['enabled'])><span></span></label></div></div>
  <div class="fld half"><label for="label">Nama petugas yang dilihat pengunjung</label><input id="label" name="label" value="{{ old('label', $cfg['label']) }}"></div>
  <div class="fld half"><label for="hours">Jam layanan live chat</label><input id="hours" name="hours" value="{{ old('hours', $cfg['hours']) }}"></div>
  <div class="fld"><label for="welcome">Pesan sambutan otomatis</label><textarea id="welcome" name="welcome" rows="2">{{ old('welcome', $cfg['welcome']) }}</textarea><div class="hint">Tulis {nama} untuk menyebut nama depan pengunjung.</div></div>
  <div class="fld"><label for="canned">Balasan cepat petugas</label><textarea id="canned" name="canned" rows="6">{{ old('canned', implode("\n", $cfg['canned'])) }}</textarea><div class="hint">Satu balasan per baris.</div></div>
  <div class="fld"><h3 style="margin:10px 0 2px;font-size:15px">Penyaring pesan</h3><div class="hint" style="margin:0">Pesan yang tersaring tidak diteruskan ke petugas. Pengunjung mendapat pemberitahuan agar memperbaiki pesannya.</div></div>
  <div class="fld"><div class="tog-row"><div><b>Saring kata kasar</b><small>Makian dan kata kotor, termasuk yang disamarkan seperti a.n.j.i.n.g atau g0bl0k. Kata seperti "anjing" tetap lolos bila dipakai wajar, misalnya "digigit anjing".</small></div><label class="switch"><input type="checkbox" name="filter_profanity" value="1" @checked($cfg['filter_profanity'] ?? true)><span></span></label></div></div>
  <div class="fld"><div class="tog-row"><div><b>Saring pesan asal-asalan</b><small>Ketikan acak (asdfgh), huruf berulang (aaaaaa), hanya simbol, atau tawa tanpa isi (wkwkwkwk).</small></div><label class="switch"><input type="checkbox" name="filter_spam" value="1" @checked($cfg['filter_spam'] ?? true)><span></span></label></div></div>
  <div class="fld half"><label for="max_strikes">Batas peringatan sebelum diblokir</label><input id="max_strikes" type="number" name="max_strikes" min="1" max="10" value="{{ old('max_strikes', $cfg['max_strikes'] ?? 3) }}"><div class="hint">Setelah sekian pesan tersaring dalam satu sesi, chat dihentikan otomatis.</div></div>
  <div class="fld half"><label for="block_hours">Lama blokir (jam)</label><input id="block_hours" type="number" name="block_hours" min="1" max="720" value="{{ old('block_hours', $cfg['block_hours'] ?? 24) }}"><div class="hint">Selama diblokir, nomor HP tersebut tidak bisa memulai live chat.</div></div>
  <div class="fld half"><label for="block_words">Kata tambahan yang disaring</label><textarea id="block_words" name="block_words" rows="4" placeholder="Satu kata per baris">{{ old('block_words', $cfg['block_words'] ?? '') }}</textarea><div class="hint">Misalnya makian dalam bahasa daerah. Satu kata per baris.</div></div>
  <div class="fld half"><label for="allow_words">Kata yang tetap diizinkan</label><textarea id="allow_words" name="allow_words" rows="4" placeholder="Satu kata per baris">{{ old('allow_words', $cfg['allow_words'] ?? '') }}</textarea><div class="hint">Bila ada kata wajar yang ikut tersaring. Satu kata per baris.</div></div>
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
    feed: @json(route('admin.livechat.feed', [], false)),
    show: @json(route('admin.livechat.show', '__id__', false)),
    send: @json(route('admin.livechat.send', '__id__', false)),
    status: @json(route('admin.livechat.status', '__id__', false)),
    note: @json(route('admin.livechat.note', '__id__', false)),
    unblock: @json(route('admin.livechat.unblock', '__id__', false))
  }
};
</script>
<script src="{{ asset('js/livechat-admin.js') }}?v=3"></script>
@endpush
@endif
@endsection
