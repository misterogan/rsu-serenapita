@extends('layouts.site')
@section('title', 'Panduan Pasien & FAQ')
@section('description', 'Panduan pendaftaran, BPJS, rawat inap, IGD, dan pertanyaan yang sering diajukan di '.$site['profile']['name'].'.')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Panduan Pasien</div><h1>Panduan pasien &amp; FAQ</h1><p>Informasi yang perlu diketahui sebelum dan selama berobat di {{ $site['profile']['name'] }}.</p></div></div>
<div class="section"><div class="wrap">
  <div class="guide-search"><input type="search" id="guideQ" placeholder="Cari, misalnya: rujukan BPJS, jam besuk, IGD" aria-label="Cari panduan dan FAQ" autocomplete="off"><span id="guideCount" aria-live="polite"></span></div>

  @if($guides->isNotEmpty())
  <h2 class="sec">Panduan pasien</h2>
  <div class="guide-grid">
    @foreach($guides as $g)
    <details class="guide card" data-search @if($loop->first) open @endif>
      <summary><span class="guide-cat">{{ $g->category }}</span><b>{{ $g->title }}</b></summary>
      <div class="guide-body">
        @foreach($g->blocks() as $blk)
          @if($blk['type'] === 'list')<ul>@foreach($blk['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>@else<p>{{ $blk['items'][0] }}</p>@endif
        @endforeach
      </div>
    </details>
    @endforeach
  </div>
  @endif

  @if($faqs->isNotEmpty())
  <h2 class="sec" style="margin-top:40px">Pertanyaan yang sering diajukan</h2>
  <div class="faq-list">
    @foreach($faqs as $f)
    <details class="faq" data-search><summary>{{ $f->question }}<small>{{ $f->category }}</small></summary><div class="faq-a">@foreach(array_filter(array_map('trim', preg_split('/\R\s*\R/', $f->answer))) as $para)<p>{{ $para }}</p>@endforeach</div></details>
    @endforeach
  </div>
  @endif

  <div class="card guide-more">
    <div><b>Belum menemukan jawabannya?</b><p>Tanyakan langsung kepada petugas kami lewat live chat, atau sampaikan saran dan keluhan Anda.</p></div>
    <div class="guide-more-act">
      @if($site['sections']['chatbot'])<a class="btn blue" href="#" data-open-chat>Tanya petugas</a>@endif
      @if($site['sections']['feedback'] ?? true)<a class="btn ghost" href="{{ route('feedback') }}">Pengaduan &amp; saran</a>@endif
    </div>
  </div>
</div></div>
<script>
(function () {
  const q = document.getElementById("guideQ"), count = document.getElementById("guideCount"); if (!q) return;
  const fold = s => s.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  const items = [...document.querySelectorAll("[data-search]")].map(el => ({ el, text: fold(el.textContent), open: el.open }));
  q.addEventListener("input", () => {
    const words = fold(q.value).split(/\s+/).filter(w => w.length > 1); let n = 0;
    items.forEach(it => {
      const ok = !words.length || words.every(w => it.text.includes(w));
      it.el.hidden = !ok; if (ok) n++;
      it.el.open = words.length ? ok : it.open;
    });
    count.textContent = words.length ? (n ? n + " hasil" : "Tidak ditemukan. Coba kata lain atau tanyakan lewat live chat.") : "";
  });
})();
</script>
@endsection
