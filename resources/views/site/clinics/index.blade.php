@extends('layouts.site')
@php $city = $site['seo']['city'] ?: 'Sidikalang'; @endphp
@section('title', 'Poliklinik di '.$city)
@section('description', 'Daftar poliklinik dan dokter spesialis di '.$site['profile']['name'].': '.$polis->pluck('name')->implode(', ').'. Lihat jadwal praktik dan buat janji online.')
@section('breadcrumb', 'Poliklinik')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Poliklinik</div><h1>Poliklinik</h1><p>Pilih poliklinik untuk melihat dokter, jadwal praktik, dan cara membuat janji di {{ $site['profile']['name'] }}.</p></div></div>
<div class="section"><div class="wrap">
  <div class="clinic-grid">
    @foreach($polis as $p)
    <a class="clinic-card card" href="{{ route('clinics.show', $p->slug()) }}">
      <h2>{{ $p->name }}</h2>
      @if($p->description)<p>{{ \App\Support\Rich::plain($p->description, 120) }}</p>@endif
      <span class="clinic-meta">{{ $p->doctors_count }} dokter · Lihat jadwal →</span>
    </a>
    @endforeach
  </div>
</div></div>
@endsection
