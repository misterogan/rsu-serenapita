@extends('layouts.site')
@section('title', 'Kontak')
@php $p = $site['profile']; @endphp
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Kontak</div><h1>Kontak dan lokasi</h1><p>{{ $site['address'] }}</p></div></div>
<div class="section"><div class="wrap contact-grid">
  <a class="map-art" href="{{ $p['maps'] ?: '#' }}" target="_blank" rel="noopener">
    <svg viewBox="0 0 400 300" aria-hidden="true"><rect width="400" height="300" fill="var(--soft)"/><path d="M0 160 L400 120" stroke="var(--line)" stroke-width="18"/><path d="M140 0 L190 300" stroke="var(--line)" stroke-width="12"/><path d="M290 0 L270 300" stroke="var(--line)" stroke-width="8"/><circle cx="210" cy="132" r="26" fill="#C8332E" opacity=".2"/><path d="M210 104c-11 0-19 8-19 18 0 14 19 32 19 32s19-18 19-32c0-10-8-18-19-18z" fill="#C8332E"/><circle cx="210" cy="122" r="7" fill="#fff"/></svg>
    <span>Buka di Google Maps</span>
  </a>
  <div>
    <dl class="info">
      <dt>Alamat</dt><dd>{{ $site['address'] }}</dd>
      @foreach($site['phones']->where('show_contact', true) as $ph)<dt>{{ $ph->label }}{{ $ph->kind === 'wa' ? ' (WhatsApp)' : '' }}</dt><dd><a href="{{ $ph->href() }}">{{ $ph->number }}</a></dd>@endforeach
      @if($p['email'])<dt>Email</dt><dd><a href="mailto:{{ $p['email'] }}">{{ $p['email'] }}</a></dd>@endif
      <dt>Loket pendaftaran</dt><dd>{{ $p['counter_hours'] }}</dd>
      <dt>Jam besuk</dt><dd>{{ $p['visiting_hours'] }}</dd>
    </dl>
    <p style="margin-top:26px"><a class="btn blue" href="{{ route('appointment') }}">Buat janji temu</a></p>
  </div>
</div></div>
@endsection
