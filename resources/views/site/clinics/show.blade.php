@extends('layouts.site')
@php
  $city = $site['seo']['city'] ?: 'Sidikalang';
  $docNames = $doctors->pluck('name')->take(4)->implode(', ');
@endphp
@section('title', $poli->name.' di '.$city)
@section('description', \Illuminate\Support\Str::limit(($poli->description ? rtrim($poli->description, '. ').'. ' : '').$poli->name.' '.$site['profile']['name'].($docNames ? ' bersama '.$docNames : '').'. Lihat jadwal praktik dan buat janji online.', 158))
@section('breadcrumb_parent', 'Poliklinik|'.route('clinics.index'))
@section('breadcrumb', $poli->name)
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / <a href="{{ route('clinics.index') }}">Poliklinik</a> / {{ $poli->name }}</div>
  <h1>{{ $poli->name }}</h1>
  <p>{{ $poli->description ?: $poli->name.' di '.$site['profile']['name'].', '.$city.'.' }}</p>
</div></div>
<div class="section"><div class="wrap">
  <div class="clinic-info card">
    <div><small>Dokter</small><b>{{ $doctors->count() }} dokter</b></div>
    <div><small>Pendaftaran</small><b>Online, telepon, atau loket</b></div>
    <div><small>Cara bayar</small><b>BPJS, umum, dan asuransi</b></div>
    @if(\App\Support\Links::enabled('appointment'))<a class="btn blue" href="{{ route('appointment', ['poli' => $poli->id]) }}">Buat janji di {{ $poli->name }}</a>@endif
  </div>

  <h2 class="sec" style="margin-top:34px">Dokter dan jadwal praktik</h2>
  @if($doctors->isEmpty())
    <p class="empty">Jadwal dokter untuk poli ini belum tersedia. Hubungi loket pendaftaran untuk informasi.</p>
  @else
  <div class="doc-grid">
    @foreach($doctors as $d)@include('site.partials.doctor-card', ['d' => $d, 'hidePoli' => true])@endforeach
  </div>
  @endif

  @if($others->isNotEmpty())
  <h2 class="sec" style="margin-top:40px">Poliklinik lainnya</h2>
  <div class="clinic-links">@foreach($others as $o)<a href="{{ route('clinics.show', $o->slug()) }}">{{ $o->name }}</a>@endforeach</div>
  @endif
</div></div>
@php
  $ld = ['@context' => 'https://schema.org', '@type' => 'MedicalClinic', 'name' => $poli->name.' – '.$site['profile']['name'], 'url' => url()->current(),
    'description' => $poli->description ?: null,
    'parentOrganization' => ['@type' => 'Hospital', 'name' => $site['profile']['name'], 'url' => url('/')],
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $site['profile']['street'], 'addressLocality' => $city, 'addressRegion' => $site['profile']['province'], 'addressCountry' => 'ID'],
    'employee' => $doctors->map(fn ($d) => array_filter(['@type' => 'Physician', 'name' => $d->name, 'image' => $d->photo ? asset('storage/'.$d->photo) : null]))->values()->all(),
  ];
@endphp
<script type="application/ld+json">{!! json_encode(array_filter($ld, fn ($v) => $v !== null && $v !== [] && $v !== ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
