@extends('layouts.site')
@php use App\Admin\Resources; @endphp
@section('title', 'Layanan')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Layanan</div><h1>Layanan rumah sakit</h1><p>Fasilitas penunjang dan poliklinik yang tersedia.</p></div></div>
<div class="section"><div class="wrap">
  <div class="svc-grid">
    @foreach($services as $s)<div class="svc"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ Resources::ICONS[$s->icon] ?? Resources::ICONS['cross'] }}"/></svg><h3>{{ $s->name }}</h3><p>{{ $s->description }}</p></div>@endforeach
    @foreach($polis as $p)<div class="svc"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ Resources::ICONS['cross'] }}"/></svg><h3>{{ $p->name }}</h3><p>{{ $p->description }}</p></div>@endforeach
  </div>
</div></div>
@endsection
