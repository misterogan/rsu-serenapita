@extends('layouts.site')
@section('title', 'Galeri')
@section('description', 'Foto dan video kegiatan, fasilitas, dan suasana '.$site['profile']['name'].'.')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Galeri</div><h1>Galeri</h1><p>Foto dan video kegiatan, fasilitas, dan suasana rumah sakit.</p></div></div>
<div class="section"><div class="wrap">
  <div class="filter" role="group" aria-label="Saring galeri">
    <a class="chip {{ !request('type') ? 'on' : '' }}" href="{{ route('gallery', array_filter(['album' => request('album')])) }}">Semua</a>
    <a class="chip {{ request('type') === 'photo' ? 'on' : '' }}" href="{{ route('gallery', array_filter(['type' => 'photo', 'album' => request('album')])) }}">Foto</a>
    <a class="chip {{ request('type') === 'video' ? 'on' : '' }}" href="{{ route('gallery', array_filter(['type' => 'video', 'album' => request('album')])) }}">Video</a>
    @foreach($albums as $al)<a class="chip {{ request('album') === $al ? 'on' : '' }}" href="{{ route('gallery', array_filter(['type' => request('type'), 'album' => request('album') === $al ? null : $al])) }}">{{ $al }}</a>@endforeach
  </div>
  @if($items->isEmpty())
    <div class="card empty-state"><p>Belum ada foto atau video untuk ditampilkan.</p></div>
  @else
  <div class="gal-grid">
    @foreach($items as $g)
    <button type="button" class="gal-item {{ $g->type }}" data-gal="{{ $loop->index }}" aria-label="Buka {{ $g->type === 'video' ? 'video' : 'foto' }}: {{ $g->title }}">
      @if($g->thumbUrl())<img src="{{ $g->thumbUrl() }}" alt="" loading="lazy">@endif
      @if($g->type === 'video')<span class="gal-play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>@endif
      <span class="gal-cap"><b>{{ $g->title }}</b>@if($g->album || $g->taken_at)<small>{{ collect([$g->album, $g->taken_at?->translatedFormat('d M Y')])->filter()->implode(' · ') }}</small>@endif</span>
    </button>
    @endforeach
  </div>
  @endif
</div></div>

<div class="lightbox" id="lightbox" hidden role="dialog" aria-modal="true" aria-label="Galeri">
  <button class="lb-close" type="button" aria-label="Tutup">×</button>
  <button class="lb-nav prev" type="button" aria-label="Sebelumnya">‹</button>
  <figure class="lb-body"><div class="lb-media"></div><figcaption></figcaption></figure>
  <button class="lb-nav next" type="button" aria-label="Berikutnya">›</button>
</div>
@php $galData = $items->map(fn ($g) => ['type' => $g->type, 'title' => $g->title, 'caption' => $g->caption, 'img' => $g->image ? asset('storage/'.$g->image) : null, 'yt' => $g->videoId()])->values(); @endphp
<script>
(function () {
  const items = @json($galData);
  const lb = document.getElementById("lightbox"); if (!lb || !items.length) return;
  const media = lb.querySelector(".lb-media"), cap = lb.querySelector("figcaption"); let cur = 0, opener = null;
  const esc = s => String(s ?? "").replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));
  function show(i) {
    cur = (i + items.length) % items.length; const it = items[cur];
    media.innerHTML = it.type === "video" && it.yt
      ? `<iframe src="https://www.youtube-nocookie.com/embed/${it.yt}?autoplay=1&rel=0" title="${esc(it.title)}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>`
      : `<img src="${esc(it.img)}" alt="${esc(it.title)}">`;
    cap.innerHTML = `<b>${esc(it.title)}</b>${it.caption ? `<span>${esc(it.caption)}</span>` : ""}<small>${cur + 1} / ${items.length}</small>`;
  }
  function open(i, el) { opener = el; lb.hidden = false; document.body.style.overflow = "hidden"; show(i); lb.querySelector(".lb-close").focus(); }
  function close() { lb.hidden = true; media.innerHTML = ""; document.body.style.overflow = ""; opener && opener.focus(); }
  document.querySelectorAll("[data-gal]").forEach(b => b.addEventListener("click", () => open(+b.dataset.gal, b)));
  lb.querySelector(".lb-close").onclick = close;
  lb.querySelector(".prev").onclick = () => show(cur - 1);
  lb.querySelector(".next").onclick = () => show(cur + 1);
  lb.addEventListener("click", e => { if (e.target === lb) close(); });
  document.addEventListener("keydown", e => { if (lb.hidden) return; if (e.key === "Escape") close(); if (e.key === "ArrowLeft") show(cur - 1); if (e.key === "ArrowRight") show(cur + 1); });
  let sx = null; lb.addEventListener("touchstart", e => sx = e.touches[0].clientX, { passive: true });
  lb.addEventListener("touchend", e => { if (sx === null) return; const dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) show(cur + (dx < 0 ? 1 : -1)); sx = null; });
})();
</script>
@endsection
