@extends('layouts.admin', ['current' => 'menu'])
@section('title', 'Menu & tampilan')
@php $p = \App\Support\SiteSettings::profile(); $logo = \App\Support\SiteSettings::logoUrl(); @endphp
@section('content')
<form method="post">
@csrf
<x-admin-head module="menu" desc="Sembunyikan atau munculkan menu dan bagian halaman situs, ubah label, dan atur urutan menu.">
  <button class="btn pri">Simpan perubahan</button>
</x-admin-head>
<div class="card" style="margin-bottom:20px"><div class="card-h"><div><h2>Pratinjau header situs</h2><p>Menu yang terlihat pengunjung</p></div></div><div class="card-b">
  <div class="nav-prev" id="navPrev"><div class="lg">@if($logo)<img src="{{ $logo }}" alt="">@else @include('partials.logo-mark', ['size' => 22]) @endif {{ $p['name'] }}</div><div class="items" style="display:flex;gap:6px"></div></div>
</div></div>
<div class="card" style="margin-bottom:20px"><div class="card-h"><div><h2>Menu navigasi utama</h2><p>Beranda selalu tampil.</p></div></div>
  <input type="hidden" name="order" id="menuOrder">
  <ul class="menu-rows" id="menuRows">
  @foreach($menus as $m)
    <li data-id="{{ $m->id }}">
      <div class="mv"><button type="button" class="ibtn" data-up aria-label="Naikkan"><svg class="i" viewBox="0 0 24 24"><path d="M6 15l6-6 6 6"/></svg></button><button type="button" class="ibtn" data-down aria-label="Turunkan"><svg class="i" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></button></div>
      <input type="text" name="menus[{{ $m->id }}][label]" value="{{ $m->label }}" aria-label="Label menu" maxlength="30">
      <code>{{ parse_url(\App\Support\Links::url($m->route), PHP_URL_PATH) ?: '/' }}</code>
      <label class="switch"><input type="checkbox" data-vis name="menus[{{ $m->id }}][visible]" value="1" @checked($m->visible) @disabled($m->locked) aria-label="Tampilkan {{ $m->label }}"><span></span></label>
      @if($m->locked)<input type="hidden" name="menus[{{ $m->id }}][visible]" value="1">@endif
    </li>
  @endforeach
  </ul>
</div>
<div class="g2">
  @foreach($groups as $g => $items)
  <div class="card"><div class="card-h"><h2>{{ $g }}</h2></div><div class="card-b tog-list">
    @foreach($items as $k => [$label, $desc])
    <div class="tog-row"><div><b>{{ $label }}</b><small>{{ $desc }}</small></div><label class="switch"><input type="checkbox" name="sections[{{ $k }}]" value="1" @checked($sections[$k] ?? true) aria-label="{{ $label }}"><span></span></label></div>
    @endforeach
  </div></div>
  @endforeach
</div>
</form>
@endsection
