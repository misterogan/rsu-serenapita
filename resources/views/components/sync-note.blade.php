@props(['title' => 'Data dari SIMRS'])
@php $simrs = \App\Support\SiteSettings::get('simrs'); @endphp
<div class="sync-note"><svg class="i" viewBox="0 0 24 24"><path d="M4 8h13M14 5l3 3-3 3M20 16H7M10 13l-3 3 3 3"/></svg>
  <div><b>{{ $title }}</b><span>{{ $slot->isNotEmpty() ? $slot : 'Perubahan dilakukan di SIMRS dan masuk ke sini otomatis.' }} Sinkron terakhir {{ $simrs['last_sync'] ? \Illuminate\Support\Carbon::parse($simrs['last_sync'])->diffForHumans() : 'belum pernah' }}.</span></div></div>
