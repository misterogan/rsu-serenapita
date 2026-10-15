@php
  use App\Support\Links;
  $p = $site['profile']; $sec = $site['sections'];
@endphp
<!DOCTYPE html>
<html lang="id" class="{{ !empty($sec['dark_mode']) ? 'auto-dark' : '' }} {{ $site['logoDark'] && $site['logoDark'] !== $site['logo'] ? 'has-dark-logo' : '' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="{{ !empty($sec['dark_mode']) ? 'light dark' : 'only light' }}">
<meta name="theme-color" content="#1C3B70">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@hasSection('title')@yield('title') · @endif{{ $p['name'] }}</title>
<meta name="description" content="@yield('description', $p['footer'])">
@php
  $ogTitle = trim($__env->yieldContent('title')) !== '' ? $__env->yieldContent('title').' · '.$p['name'] : $p['name'].' – '.$p['short'];
  $ogDesc = trim($__env->yieldContent('description')) ?: $p['footer'];
  $ogCustom = trim($__env->yieldContent('og_image'));
  $ogImage = $ogCustom ?: \App\Support\SiteSettings::shareImageUrl();
@endphp
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:site_name" content="{{ $p['name'] }}">
<meta property="og:locale" content="id_ID">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDesc }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $p['name'] }}">
@unless($ogCustom)<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">@endunless
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDesc }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@if(request()->routeIs('home'))
@php
  $ld = [
    '@context' => 'https://schema.org',
    '@type' => 'Hospital',
    'name' => $p['name'],
    'url' => url('/'),
    'image' => $ogImage,
    'logo' => $site['logo'] ?? asset('images/og-default.jpg'),
    'telephone' => $site['emergency']?->number,
    'email' => $p['email'] ?: null,
    'address' => [
      '@type' => 'PostalAddress',
      'streetAddress' => $p['street'],
      'addressLocality' => trim(preg_replace('/^Kec(\.|amatan)?\s*/i', '', $p['district'] ?? '')) ?: $p['city'],
      'addressRegion' => $p['province'],
      'postalCode' => $p['zip'],
      'addressCountry' => 'ID',
    ],
    'hasMap' => $p['maps'] ?: null,
    'isAcceptingNewPatients' => true,
  ];
@endphp
<script type="application/ld+json">{!! json_encode(array_filter($ld, fn ($v) => $v !== null && $v !== ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
<link rel="icon" href="{{ $site['logo'] ?? 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"><path d="M20 2 36 11v18L20 38 4 29V11z" fill="#35A3D8"/><path d="M17 12h6v5h5v6h-5v5h-6v-5h-5v-6h5z" fill="#fff"/></svg>') }}">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v=8">
</head>
<body>
@if($sec['loader'])
<div id="loader" role="status" aria-hidden="true"><div class="ld-card">
  <div class="ld-logo">@if($site['logo'])<img src="{{ $site['logo'] }}" alt="">@else @include('partials.logo-mark', ['size' => 62]) @endif</div>
  <svg class="ld-ecg" viewBox="0 0 200 46" aria-hidden="true"><path class="base" d="M0 25h50l8-14 10 30 10-38 9 30 7-8h106"/><path class="trace" d="M0 25h50l8-14 10 30 10-38 9 30 7-8h106"/></svg>
  <span>Memuat halaman…</span>
</div></div>
@endif

@if($sec['topbar'])
<div class="topbar"><div class="wrap">
  <span class="addr">{{ $site['shortAddress'] }}</span>
  <span class="phones">@foreach($site['phones']->where('show_top', true) as $ph){{ $ph->label }}: <a href="{{ $ph->href() }}">{{ $ph->number }}</a>@if(!$loop->last) · @endif @endforeach</span>
</div></div>
@endif

<header class="site"><div class="wrap">
  <a class="logo" href="{{ route('home') }}" aria-label="{{ $p['name'] }}, ke beranda">
    @if($site['logo'])<picture>@if(!empty($sec['dark_mode']) && $site['logoDark'] && $site['logoDark'] !== $site['logo'])<source srcset="{{ $site['logoDark'] }}" media="(prefers-color-scheme: dark)">@endif<img src="{{ $site['logo'] }}" alt=""></picture>@else @include('partials.logo-mark') @endif
    <div><strong>{{ $p['name'] }}</strong><span>{{ $p['short'] }}</span></div>
  </a>
  <button class="menu-btn" id="menuBtn" aria-expanded="false" aria-controls="nav">Menu</button>
  <nav class="main" id="nav">
    @foreach($site['menus'] as $m)@continue(!Links::enabled($m->route))<a href="{{ Links::url($m->route) }}" class="{{ Links::active($m->route) ? 'active' : '' }}">{{ $m->label }}</a>@endforeach
  </nav>
</div></header>

<main>@yield('content')</main>

<footer>
  <div class="wrap grid">
    <div>
      <div class="flogo">@if($site['logoDark'])<img src="{{ $site['logoDark'] }}" alt="">@else @include('partials.logo-mark', ['size' => 30]) @endif {{ $p['name'] }}</div>
      <p style="margin:0 0 14px">{{ $p['footer'] }}</p>
      <p style="margin:0 0 8px">{{ $site['address'] }}</p>
      @foreach($site['phones']->where('show_footer', true) as $ph)<div>{{ $ph->label }}: <a href="{{ $ph->href() }}">{{ $ph->number }}</a></div>@endforeach
      @if($p['email'])<div>Email: <a href="mailto:{{ $p['email'] }}">{{ $p['email'] }}</a></div>@endif
    </div>
    <div>
      @if($sec['footer_news'])
      <h4>Berita terbaru</h4>
      <ul>@foreach($site['footerNews'] as $n)<li><a href="{{ route('news.show', $n->slug) }}">{{ $n->title }}</a><small>{{ $n->published_at->translatedFormat('d M Y') }}</small></li>@endforeach</ul>
      @endif
    </div>
    <div>
      @if($sec['footer_links'])
      <h4>Tautan cepat</h4>
      <ul>
        @if(Links::enabled('schedule'))<li><a href="{{ route('schedule') }}">Jadwal praktik dokter</a></li>@endif
        @if(Links::enabled('appointment'))<li><a href="{{ route('appointment') }}">Buat janji temu</a></li>@endif
        @if(Links::enabled('doctors'))<li><a href="{{ route('doctors') }}">Daftar dokter</a></li>@endif
        @if(Links::enabled('services'))<li><a href="{{ route('services') }}">Layanan dan fasilitas</a></li>@endif
        @if($sec['careers'] ?? true)<li><a href="{{ route('careers.index') }}">Karier &amp; lowongan kerja</a></li>@endif
        @if($sec['chatbot'])<li><a href="#" data-open-chat>Tanya {{ $site['chatbot']['name'] }} atau petugas</a></li>@endif
      </ul>
      @endif
    </div>
  </div>
  <div class="wrap copy"><span>© {{ date('Y') }} {{ $p['name'] }}</span>@if($site['emergency'])<span>{{ $site['emergency']->label }} {{ $site['emergency']->number }}</span>@endif</div>
</footer>

@if($sec['chatbot'])
<button class="chat-fab" id="chatFab" aria-controls="chat" aria-expanded="false">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg>Chat dengan kami
</button>
<div class="chat" id="chat" role="dialog" aria-label="Chat">
  <div class="chat-head">
    <div class="av">@include('partials.logo-mark', ['size' => 26])</div>
    <div><b>{{ $site['chatbot']['name'] }}</b><small>Asisten informasi</small></div>
    <button id="chatClose" aria-label="Tutup chat">×</button>
  </div>
  <div class="chat-tabs" role="tablist">
    @if($site['chatbot']['enabled'])<button role="tab" aria-selected="true" data-ct="bot">Asisten {{ $site['chatbot']['name'] }}</button>@endif
    <button role="tab" aria-selected="{{ $site['chatbot']['enabled'] ? 'false' : 'true' }}" data-ct="live">Live chat petugas</button>
  </div>
  <div class="pane" id="botPane" @unless($site['chatbot']['enabled']) hidden @endunless>
    <div class="chat-log" id="botLog" aria-live="polite"></div>
    <div class="quick" id="botQuick">@foreach($site['chatbot']['quick'] as $q)<button type="button">{{ $q }}</button>@endforeach</div>
    <form class="chat-form" id="botForm" data-noload><input id="botIn" placeholder="Tulis pertanyaan Anda" aria-label="Pertanyaan" autocomplete="off" maxlength="500"><button type="submit">Kirim</button></form>
    <div class="chat-note">{{ $site['chatbot']['name'] }} memberi informasi umum, bukan diagnosis. Keadaan darurat: segera ke IGD.</div>
  </div>
  <div class="pane" id="livePane" @if($site['chatbot']['enabled']) hidden @endif></div>
</div>
@endif

<script>
window.SERENA = {
  csrf: @json(csrf_token()),
  loader: @json((bool) $sec['loader']),
  phone: @json($site['emergency']?->number ?? ''),
  pollMs: @json(config('serenapita.livechat.visitor_poll_ms')),
  bot: { greeting: @json($site['chatbot']['greeting']) },
  livechat: { enabled: @json((bool) $site['livechat']['enabled']), label: @json($site['livechat']['label']), hours: @json($site['livechat']['hours']) },
  routes: { chatbot: @json(route('chatbot')), times: @json(route('appointment.times')), lcStart: @json(route('livechat.start')), lcPoll: @json(route('livechat.poll')), lcSend: @json(route('livechat.send')), lcEnd: @json(route('livechat.end')) }
};
</script>
<script src="{{ asset('js/site.js') }}?v=4" defer></script>
@if($sec['chatbot'] && !$site['chatbot']['enabled'])<script>document.addEventListener("DOMContentLoaded",()=>document.querySelector('[data-ct="live"]')?.click());</script>@endif
</body>
</html>
