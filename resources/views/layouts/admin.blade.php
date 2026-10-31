@php
  $me = auth()->user();
  $current = $current ?? request()->segment(2);
  $lcCount = $me->canModule('livechat') ? \App\Models\ChatConversation::where('status', 'menunggu')->count() : 0;
  $routeFor = fn ($k) => url('admin/'.$k);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex">
<title>@yield('title', 'Backoffice') · {{ $brand['profile']['name'] }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=13">
</head>
<body data-lc-count="{{ $me->canModule('livechat') ? route('admin.livechat.count', [], false) : '' }}">
<div id="loader" role="status" aria-hidden="true"><div class="ld-card">
  <div class="ld-logo">@include('partials.logo-mark', ['size' => 60])</div>
  <svg class="ld-ecg" viewBox="0 0 200 46" aria-hidden="true"><path class="base" d="M0 25h50l8-14 10 30 10-38 9 30 7-8h106"/><path class="trace" d="M0 25h50l8-14 10 30 10-38 9 30 7-8h106"/></svg>
  <span id="ldText">Memuat…</span>
</div></div>

<div class="app">
  <aside class="side" id="side">
    <a class="side-logo" href="{{ route('admin.home') }}">
      @if($brand['logoDark'])<img src="{{ $brand['logoDark'] }}" alt="">@else @include('partials.logo-mark', ['size' => 36]) @endif
      <div><strong>{{ $brand['profile']['name'] }}</strong><span>Backoffice</span></div>
    </a>
    <nav aria-label="Menu backoffice">
      @if($me->isExecutive())
        <div class="grp">Manajemen</div>
        <a href="{{ route('admin.management') }}" data-label="Dasbor manajemen" class="{{ $current === 'management' ? 'active' : '' }}"><svg class="i" viewBox="0 0 24 24"><path d="{{ $icons['management'] }}"/></svg>Dasbor manajemen</a>
      @endif
      @foreach($modules as $group => $items)
        @php $visible = collect($items)->filter(fn ($n, $k) => $me->canModule($k)); @endphp
        @if($visible->isNotEmpty())
        <div class="grp">{{ $group }}</div>
        @foreach($visible as $key => $name)
          <a href="{{ $routeFor($key) }}" data-label="{{ $name }}" class="{{ $current === $key ? 'active' : '' }}" @if($current === $key) aria-current="page" @endif>
            <svg class="i" viewBox="0 0 24 24"><path d="{{ $icons[$key] }}"/></svg>{{ $name }}
            @if($key === 'feedback')@php $fbNew = \App\Models\Feedback::where('status', 'Baru')->count(); @endphp @if($fbNew)<span class="cnt" title="Laporan baru">{{ $fbNew }}</span>@endif @endif
            @if($key === 'livechat')<span class="cnt" id="lcBadge" data-n="{{ $lcCount }}" @if(!$lcCount) hidden @endif>{{ $lcCount ?: '' }}</span>@endif
          </a>
        @endforeach
        @endif
      @endforeach
      @unless($me->isExecutive())
      <div class="grp">Bantuan</div>
      <a href="{{ route('admin.help') }}" data-label="Panduan" class="{{ $current === 'help' ? 'active' : '' }}"><svg class="i" viewBox="0 0 24 24"><path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2zM4 21V5M9 7h6M9 11h6"/></svg>Panduan</a>
      @endunless
    </nav>
    <div class="side-user">
      <div class="av" style="background:#35A3D8">{{ $me->initials() }}</div>
      <div><b>{{ $me->name }}</b><small>{{ $me->role?->name }}</small></div>
      <a class="side-key" href="{{ route('admin.password') }}" title="Ganti kata sandi" aria-label="Ganti kata sandi"><svg class="i" viewBox="0 0 24 24"><path d="M15 7a4 4 0 1 1-3.9 4.9L4 19v2h3v-2h2v-2h2l1.1-1.1A4 4 0 0 1 15 7zM16 9.5h.01"/></svg></a>
      <form method="post" action="{{ route('admin.logout') }}">@csrf<button title="Keluar" aria-label="Keluar"><svg class="i" viewBox="0 0 24 24"><path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/></svg></button></form>
    </div>
  </aside>
  <div>
    <header class="top">
      <button class="burger" id="burger" aria-label="Buka menu"><svg class="i" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
      <div class="top-acts">
        @php $helpAnchor = ['applications' => 'jobs', 'leaves' => 'schedule', 'faqs' => 'guides', 'stock-report' => 'stock', 'medicines' => 'stock', 'bmhp' => 'stock', 'help' => ''][$current] ?? $current; @endphp
        @if($current !== 'help' && !$me->isExecutive())<a class="btn sm" id="helpBtn" href="{{ route('admin.help') }}{{ $helpAnchor ? '#'.$helpAnchor : '' }}" title="Panduan untuk halaman ini"><svg class="i" viewBox="0 0 24 24"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.5-3 4.5M12 17.5h.01"/></svg>Panduan</a>@endif
        <a class="btn sm" href="{{ route('home') }}" target="_blank" rel="noopener"><svg class="i" viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/></svg>Lihat situs</a>
      </div>
    </header>
    <main class="content">
      @if(session('ok'))<div class="flash ok" role="status">{{ session('ok') }}</div>@endif
      @if(session('err'))<div class="flash err" role="alert">{{ session('err') }}</div>@endif
      @if($errors->any())<div class="flash err" role="alert">{{ $errors->first() }}@if($errors->count() > 1) (dan {{ $errors->count() - 1 }} kesalahan lain)@endif</div>@endif
      @yield('content')
    </main>
  </div>
</div>

<div class="modal" id="modal" role="alertdialog" aria-modal="true" aria-labelledby="mdTitle"><div class="box"><h3 id="mdTitle">Yakin?</h3><p id="mdText"></p><div class="acts"><button class="btn" id="mdNo" type="button">Batal</button><button class="btn danger" id="mdYes" type="button">Ya, lanjutkan</button></div></div></div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script src="{{ asset('js/admin.js') }}?v=10"></script>
@stack('tour')
<script src="{{ asset('js/tour.js') }}?v=2"></script>
@stack('scripts')
</body>
</html>
