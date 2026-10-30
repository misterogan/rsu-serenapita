@extends('layouts.site')
@php use App\Support\Links; $sec = $site['sections']; $em = $site['emergency']; @endphp
@section('content')
@if($sec['slider'] && $slides->isNotEmpty())
<section class="hero">
  <svg class="hero-art" viewBox="0 0 1440 560" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
    <circle cx="1180" cy="120" r="70" fill="#fff" opacity=".08"/>
    <path d="M0 400 C220 330 380 360 560 320 S900 250 1100 300 1350 270 1440 290 V560 H0Z" fill="#fff" opacity=".06"/>
    <path d="M0 450 C260 400 420 430 640 390 S1000 340 1200 380 1380 360 1440 370 V560 H0Z" fill="#fff" opacity=".07"/>
    <g transform="translate(860 150)"><rect x="60" y="70" width="380" height="260" fill="#fff" opacity=".12"/><rect x="170" y="10" width="160" height="320" fill="#fff" opacity=".18"/><rect x="222" y="40" width="56" height="56" fill="#fff"/><path d="M242 44h16v16h16v16h-16v16h-16V76h-16V60h16z" fill="#C8332E"/>
      <g fill="#fff" opacity=".35"><rect x="85" y="100" width="28" height="20"/><rect x="125" y="100" width="28" height="20"/><rect x="85" y="140" width="28" height="20"/><rect x="125" y="140" width="28" height="20"/><rect x="347" y="100" width="28" height="20"/><rect x="387" y="100" width="28" height="20"/><rect x="347" y="140" width="28" height="20"/><rect x="387" y="140" width="28" height="20"/><rect x="195" y="120" width="110" height="14"/><rect x="195" y="150" width="110" height="14"/></g>
      <rect x="215" y="260" width="70" height="70" fill="#fff" opacity=".3"/></g>
  </svg>
  @foreach($slides as $i => $s)
    @if($s->image)
      @php $bgUrl = asset('storage/'.$s->image); $fit = $s->fitMode(); @endphp
      @if($fit === 'contain')
      <div class="hero-bg fit-contain {{ $i === 0 ? 'on' : '' }}" data-bg="{{ $i }}" role="img" aria-label="{{ $s->title }}">
        <span class="hero-blur" style="background-image:url('{{ $bgUrl }}');background-position:{{ $s->image_position ?: 'center' }}"></span>
        <img class="hero-fg" src="{{ $bgUrl }}" alt="">
      </div>
      @else
      <div class="hero-bg {{ $i === 0 ? 'on' : '' }}" data-bg="{{ $i }}" style="background-image:url('{{ $bgUrl }}');background-position:{{ $s->image_position ?: 'center' }}" role="img" aria-label="{{ $s->title }}"></div>
      @endif
    @endif
  @endforeach
  <div class="wrap">
    <div class="slides">
      @foreach($slides as $i => $s)
      <div class="slide {{ $i === 0 ? 'on' : '' }}">
        <h1>{{ $s->title }}</h1>
        @if($s->text)<p>{{ $s->text }}</p>@endif
        @if($s->button && Links::enabled($s->link))<a class="btn solid" href="{{ Links::url($s->link) }}">{{ $s->button }}</a>@endif
      </div>
      @endforeach
    </div>
    @if($slides->count() > 1)
    <div class="dots" role="tablist" aria-label="Pilih slide">
      @foreach($slides as $i => $s)<button role="tab" aria-current="{{ $i === 0 ? 'true' : 'false' }}" data-go="{{ $i }}">{{ $s->tab ?: 'Slide '.($i + 1) }}</button>@endforeach
    </div>
    @endif
  </div>
</section>
@endif

@if($sec['boxes'])
<div class="boxes" @unless($sec['slider'] && $slides->isNotEmpty()) style="margin-top:30px" @endunless><div class="wrap"><div class="grid">
  @foreach($boxes as $i => $b)
  <div class="box b{{ $i + 1 }}">
    <h3>{{ $b['title'] }}</h3>
    @if($i === 2)
      <ul class="hours">@foreach($hours as $h)<li><span>{{ $h['label'] }}</span><span>{{ $h['value'] }}</span></li>@endforeach</ul>
    @else
      @if(!empty($b['show_phone']) && $em)<a class="big-phone" href="{{ $em->href() }}">{{ $em->number }}</a>@endif
      @if($b['text'])<p>{{ $b['text'] }}</p>@endif
      @if($b['button'] && Links::enabled($b['link']))<a class="btn" href="{{ Links::url($b['link']) }}">{{ $b['button'] }}</a>@endif
    @endif
  </div>
  @endforeach
</div></div></div>
@endif

<div class="section"><div class="wrap cols">
  <div>
    @if($sec['news'])
    <h2 class="sec">Berita terbaru</h2>
    <div class="news-grid">@foreach($news as $i => $n)@include('partials.post', ['n' => $n])@endforeach</div>
    @if(Links::enabled('news'))<p style="text-align:right;margin-top:20px"><a href="{{ route('news.index') }}" style="font-weight:700;font-size:14px">Semua berita</a></p>@endif
    @endif
  </div>
  <aside>
    @if($sec['poli_side'] && $featuredPoli)
    <h2 class="sec">Poliklinik</h2>
    <div class="dept-feature"><h4>{{ $featuredPoli->name }}</h4><div class="body">{{ \App\Support\Rich::plain($featuredPoli->description, 220) }}</div>
      @if(Links::enabled('doctors') || Links::enabled('appointment'))<div class="acts">@if(Links::enabled('doctors'))<a href="{{ route('clinics.show', $featuredPoli->slug()) }}">Dokter &amp; jadwal</a>@endif @if(Links::enabled('appointment'))<a href="{{ route('appointment', ['poli' => $featuredPoli->id]) }}">Buat janji</a>@endif</div>@endif</div>
    <ul class="dept-list">@foreach($polis->where('id', '!=', $featuredPoli->id) as $p)<li>@if(Links::enabled('doctors'))<a href="{{ route('clinics.show', $p->slug()) }}">{{ $p->name }}<span aria-hidden="true">+</span></a>@else<span class="dept-plain">{{ $p->name }}</span>@endif</li>@endforeach</ul>
    @endif
    @if($sec['book_side'])
    <h2 class="sec" style="margin-top:40px">Buat janji temu</h2>
    <p style="font-size:14px;color:var(--muted);margin:0">Daftar poli secara online, lewat telepon, atau datang ke loket pendaftaran.</p>
    <ul class="contact-mini">
      @foreach($site['phones']->where('show_contact', true)->take(2) as $ph)<li><i>{{ $ph->kind === 'wa' ? '✆' : '☎' }}</i><span>{{ $ph->label }} <b><a href="{{ $ph->href() }}">{{ $ph->number }}</a></b></span></li>@endforeach
      @if($site['profile']['email'])<li><i>✉</i><span>Email <b>{{ $site['profile']['email'] }}</b></span></li>@endif
      @if(Links::enabled('appointment'))<li><i>✎</i><span><a href="{{ route('appointment') }}">Isi formulir janji temu</a> di situs ini</span></li>@endif
    </ul>
    @endif
  </aside>
</div></div>
@endsection
