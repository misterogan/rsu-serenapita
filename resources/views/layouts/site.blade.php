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
@php
  $seo = $site['seo']; $isHome = request()->routeIs('home');
  $pageTitle = $isHome ? ($seo['home_title'] ?: $p['name']) : (trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')).' – '.$p['name'] : $p['name']);
  $pageDesc = $isHome ? ($seo['home_description'] ?: $p['footer']) : (trim($__env->yieldContent('description')) ?: $p['footer']);
  $altNames = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $seo['alt_names']))));
@endphp
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit($pageDesc, 165) }}">
<link rel="canonical" href="{{ url()->current() }}">
@php
  $ogTitle = trim($__env->yieldContent('title')) !== '' ? $__env->yieldContent('title').' · '.$p['name'] : $p['name'].' – '.$p['short'];
  $ogDesc = $pageDesc;
  $ogCustom = trim($__env->yieldContent('og_image'));
  $ogImage = $ogCustom ?: \App\Support\SiteSettings::shareImageUrl();
@endphp
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
@php
  $hospital = array_filter([
    '@type' => 'Hospital', '@id' => url('/').'#rumah-sakit',
    'name' => $p['name'], 'alternateName' => $altNames ?: null, 'url' => url('/'),
    'image' => $ogImage, 'logo' => $site['logo'] ?? asset('images/og-default.jpg'),
    'description' => $seo['home_description'] ?: null,
    'telephone' => $site['emergency']?->number, 'email' => $p['email'] ?: null,
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $p['street'],
      'addressLocality' => trim(preg_replace('/^Kec(\.|amatan)?\s*/i', '', $p['district'] ?? '')) ?: $p['city'],
      'addressRegion' => $p['province'], 'postalCode' => $p['zip'], 'addressCountry' => 'ID'],
    'hasMap' => $p['maps'] ?: null,
    'openingHours' => 'Mo-Su 00:00-23:59',
    'isAcceptingNewPatients' => true,
    'sameAs' => array_values(array_filter(array_merge(array_values($site['social']), [$p['maps'] ?: null]))) ?: null,
  ], fn ($v) => $v !== null && $v !== '');
  if ($isHome) {
      $polisLd = \App\Models\Poli::where('active', true)->orderBy('sort')->get();
      if ($polisLd->isNotEmpty() && \App\Support\Links::enabled('doctors')) {
          $hospital['department'] = $polisLd->map(fn ($pl) => ['@type' => 'MedicalClinic', 'name' => $pl->name, 'url' => route('clinics.show', $pl->slug())])->values()->all();
      }
      $graph = [
        array_filter(['@type' => 'WebSite', '@id' => url('/').'#website', 'name' => $p['name'], 'alternateName' => $altNames ?: null, 'url' => url('/'), 'inLanguage' => 'id-ID', 'publisher' => ['@id' => url('/').'#rumah-sakit']]),
        $hospital,
      ];
  } else {
      $crumbs = [['Beranda', url('/')]];
      if ($parent = trim($__env->yieldContent('breadcrumb_parent'))) { [$pl, $pu] = array_pad(explode('|', $parent, 2), 2, null); $crumbs[] = [$pl, $pu]; }
      $crumbs[] = [trim($__env->yieldContent('breadcrumb')) ?: (trim($__env->yieldContent('title')) ?: $p['name']), url()->current()];
      $graph = [
        ['@type' => 'BreadcrumbList', 'itemListElement' => collect($crumbs)->values()->map(fn ($c, $k) => array_filter(['@type' => 'ListItem', 'position' => $k + 1, 'name' => $c[0], 'item' => $c[1]]))->all()],
        ['@type' => 'Hospital', '@id' => url('/').'#rumah-sakit', 'name' => $p['name'], 'url' => url('/')],
      ];
  }
@endphp
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<link rel="icon" href="{{ $site['logo'] ?? 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"><path d="M20 2 36 11v18L20 38 4 29V11z" fill="#35A3D8"/><path d="M17 12h6v5h5v6h-5v5h-6v-5h-5v-6h5z" fill="#fff"/></svg>') }}">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v=17">
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
    @foreach($site['menus'] as $m)@continue(!Links::enabled($m->route))
      @if($m->route === 'about')
        @php
          $subs = collect([
            ['about', 'Profil rumah sakit', true],
            ['guide', 'Panduan pasien & FAQ', $sec['guide'] ?? true],
            ['gallery', 'Galeri foto & video', $sec['gallery'] ?? true],
            ['feedback', 'Pengaduan & saran', $sec['feedback'] ?? true],
            ['careers.index', 'Karier', $sec['careers'] ?? true],
          ])->filter(fn ($x) => $x[2]);
          $aboutOn = request()->routeIs('about', 'guide', 'gallery', 'feedback', 'feedback.*', 'careers.*');
        @endphp
        @if($subs->count() > 1)
        <div class="nav-dd {{ $aboutOn ? 'active' : '' }}">
          <a href="{{ route('about') }}" class="nav-dd-top">{{ $m->label }}</a><button type="button" class="nav-dd-btn" aria-expanded="false" aria-label="Submenu {{ $m->label }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></button>
          <div class="nav-dd-menu">@foreach($subs as [$r, $label])<a href="{{ route($r) }}" @if(request()->routeIs($r) || ($r === 'careers.index' && request()->routeIs('careers.*')) || ($r === 'feedback' && request()->routeIs('feedback.*'))) aria-current="page" @endif>{{ $label }}</a>@endforeach</div>
        </div>
        @else
        <a href="{{ route('about') }}" class="{{ $aboutOn ? 'active' : '' }}">{{ $m->label }}</a>
        @endif
      @else
      <a href="{{ Links::url($m->route) }}" class="{{ Links::active($m->route) ? 'active' : '' }}">{{ $m->label }}</a>
      @endif
    @endforeach
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
      @if($site['social'])<div class="foot-social">@if(!empty($site['social']['instagram']))<a href="{{ $site['social']['instagram'] }}" target="_blank" rel="noopener me" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7.3a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm6-7.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0zM12 4.6c2.4 0 2.7 0 3.6.1 2.4.1 3.6 1.3 3.7 3.7.1.9.1 1.2.1 3.6s0 2.7-.1 3.6c-.1 2.4-1.3 3.6-3.7 3.7-.9.1-1.2.1-3.6.1s-2.7 0-3.6-.1c-2.5-.1-3.6-1.3-3.7-3.7-.1-.9-.1-1.2-.1-3.6s0-2.7.1-3.6C4.8 6 6 4.8 8.4 4.7c.9-.1 1.2-.1 3.6-.1zM12 3c-2.4 0-2.7 0-3.7.1C5 3.2 3.2 5 3.1 8.3 3 9.3 3 9.6 3 12s0 2.7.1 3.7c.1 3.3 1.9 5.1 5.2 5.2 1 .1 1.3.1 3.7.1s2.7 0 3.7-.1c3.3-.1 5.1-1.9 5.2-5.2.1-1 .1-1.3.1-3.7s0-2.7-.1-3.7C20.8 5 19 3.2 15.7 3.1 14.7 3 14.4 3 12 3z"/></svg></a>@endif @if(!empty($site['social']['facebook']))<a href="{{ $site['social']['facebook'] }}" target="_blank" rel="noopener me" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.4 21v-8.2h2.8l.4-3.2h-3.2V7.6c0-.9.3-1.6 1.6-1.6h1.7V3.1c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.3H7.2v3.2H10V21h3.4z"/></svg></a>@endif @if(!empty($site['social']['youtube']))<a href="{{ $site['social']['youtube'] }}" target="_blank" rel="noopener me" aria-label="YouTube"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2C2 8.8 2 12 2 12s0 3.2.4 4.8a2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8zM10 15V9l5.2 3L10 15z"/></svg></a>@endif @if(!empty($site['social']['tiktok']))<a href="{{ $site['social']['tiktok'] }}" target="_blank" rel="noopener me" aria-label="TikTok"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.1v12.4a2.6 2.6 0 1 1-2.6-2.6c.3 0 .5 0 .8.1V9.7a5.7 5.7 0 1 0 4.9 5.7V9.1a7.3 7.3 0 0 0 4.3 1.4V7.4a4.3 4.3 0 0 1-3.2-1.6z"/></svg></a>@endif</div>@endif
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
        @if(Links::enabled('doctors'))<li><a href="{{ route('clinics.index') }}">Poliklinik</a></li>@endif
        @if(Links::enabled('appointment'))<li><a href="{{ route('appointment') }}">Buat janji temu</a></li>@endif
        @if(Links::enabled('doctors'))<li><a href="{{ route('doctors') }}">Daftar dokter</a></li>@endif
        @if(Links::enabled('services'))<li><a href="{{ route('services') }}">Layanan dan fasilitas</a></li>@endif
        @if($sec['chatbot'])<li><a href="#" data-open-chat>Tanya {{ $site['chatbot']['name'] }} atau petugas</a></li>@endif
      </ul>
      @endif
    </div>
    <div>
      @if($sec['footer_links'])
      <h4>Informasi</h4>
      <ul>
        @if(Links::enabled('about'))<li><a href="{{ route('about') }}">Tentang kami</a></li>@endif
        @if($sec['guide'] ?? true)<li><a href="{{ route('guide') }}">Panduan pasien &amp; FAQ</a></li>@endif
        @if($sec['gallery'] ?? true)<li><a href="{{ route('gallery') }}">Galeri foto &amp; video</a></li>@endif
        @if($sec['feedback'] ?? true)<li><a href="{{ route('feedback') }}">Pengaduan &amp; saran</a></li>@endif
        @if($sec['careers'] ?? true)<li><a href="{{ route('careers.index') }}">Karier &amp; lowongan kerja</a></li>@endif
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
  <div class="pane" id="botPane" @unless($site['chatbot']['enabled']) hidden @endunless>
    <div class="chat-log" id="botLog" aria-live="polite"></div>
    <div class="quick" id="botQuick">@foreach($site['chatbot']['quick'] as $q)<button type="button">{{ $q }}</button>@endforeach</div>
    @if($site['livechat']['enabled'])<button type="button" class="to-agent" data-go-live><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-7 8a7 7 0 0 1 14 0"/></svg>Chat dengan petugas</button>@endif
    <form class="chat-form" id="botForm" data-noload><input id="botIn" placeholder="Tulis pertanyaan Anda" aria-label="Pertanyaan" autocomplete="off" maxlength="500"><button type="submit">Kirim</button></form>
    <div class="chat-note">{{ $site['chatbot']['name'] }} memberi informasi umum, bukan diagnosis. Keadaan darurat: segera ke IGD.</div>
  </div>
  <div class="pane" id="livePane" @if($site['chatbot']['enabled']) hidden @endif>
    @if($site['chatbot']['enabled'])<button type="button" class="live-back" data-go-bot>← Kembali ke {{ $site['chatbot']['name'] }}</button>@endif
    <div class="live-root" id="liveRoot"></div>
  </div>
</div>
@endif

<script>
window.SERENA = {
  csrf: @json(csrf_token()),
  loader: @json((bool) $sec['loader']),
  phone: @json($site['emergency']?->number ?? ''),
  pollMs: @json(config('serenapita.livechat.visitor_poll_ms')),
  bot: { enabled: @json((bool) $site['chatbot']['enabled']), name: @json($site['chatbot']['name']), greeting: @json($site['chatbot']['greeting']) },
  livechat: { enabled: @json((bool) $site['livechat']['enabled']), label: @json($site['livechat']['label']), hours: @json($site['livechat']['hours']) },
  routes: { chatbot: @json(route('chatbot', [], false)), times: @json(route('appointment.times', [], false)), lcStart: @json(route('livechat.start', [], false)), lcPoll: @json(route('livechat.poll', [], false)), lcSend: @json(route('livechat.send', [], false)), lcEnd: @json(route('livechat.end', [], false)) }
};
</script>
<script src="{{ asset('js/site.js') }}?v=7" defer></script>
</body>
</html>
