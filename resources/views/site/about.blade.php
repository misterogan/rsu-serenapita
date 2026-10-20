@extends('layouts.site')
@section('title', 'Tentang Kami')
@section('breadcrumb', 'Tentang Kami')
@section('description', $a['intro'])
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Tentang Kami</div><h1>Tentang {{ $site['profile']['name'] }}</h1><p>{{ $a['intro'] }}</p></div></div>
<div class="section"><div class="wrap">
  @if($img('image'))<figure class="about-hero"><img src="{{ $img('image') }}" alt="{{ $site['profile']['name'] }}"></figure>@endif

  @if($history)
  <div class="about-block"><h2 class="sec">Sejarah</h2>@foreach($history as $para)<p>{{ $para }}</p>@endforeach</div>
  @endif

  @if($a['vision'] || $mission)
  <div class="about-vm">
    @if($a['vision'])<div class="card about-vision"><h2 class="sec">Visi</h2><p>{{ $a['vision'] }}</p></div>@endif
    @if($mission)<div class="card"><h2 class="sec">Misi</h2><ol class="about-list">@foreach($mission as $m)<li>{{ $m }}</li>@endforeach</ol></div>@endif
  </div>
  @endif

  @if($values)
  <div class="about-block"><h2 class="sec">Nilai kami</h2><div class="about-values">@foreach($values as $v)@php [$k, $d] = array_pad(preg_split('/\s+[–-]\s+/u', $v, 2), 2, null); @endphp<div class="about-value"><b>{{ $k }}</b>@if($d)<span>{{ $d }}</span>@endif</div>@endforeach</div></div>
  @endif

  @if($a['accreditation'] || $awards || $img('accreditation_image'))
  <div class="about-block about-acc">
    <div>
      <h2 class="sec">Akreditasi dan penghargaan</h2>
      @if($a['accreditation'])<p class="about-badge">{{ $a['accreditation'] }}</p>@endif
      @if($awards)<ul class="about-list">@foreach($awards as $w)<li>{{ $w }}</li>@endforeach</ul>@endif
    </div>
    @if($img('accreditation_image'))<a href="{{ $img('accreditation_image') }}" target="_blank" class="about-cert"><img src="{{ $img('accreditation_image') }}" alt="Sertifikat akreditasi"></a>@endif
  </div>
  @endif

  @if($a['director_message'])
  <div class="card about-director">
    @if($img('director_photo'))<img src="{{ $img('director_photo') }}" alt="{{ $a['director_name'] }}">@endif
    <div><h2 class="sec">Sambutan {{ strtolower($a['director_title'] ?: 'direktur') }}</h2>
      @foreach(array_filter(array_map('trim', preg_split('/\R\s*\R/', $a['director_message']))) as $para)<p>{{ $para }}</p>@endforeach
      @if($a['director_name'])<p class="about-sign"><b>{{ $a['director_name'] }}</b><br><span>{{ $a['director_title'] }}</span></p>@endif
    </div>
  </div>
  @endif

  @if($img('org_chart'))
  <div class="about-block"><h2 class="sec">Struktur organisasi</h2><a href="{{ $img('org_chart') }}" target="_blank" class="about-org"><img src="{{ $img('org_chart') }}" alt="Struktur organisasi"></a></div>
  @endif
</div></div>
@endsection
