<article class="post">
  <div class="meta-tag"><span>{{ $n->published_at->translatedFormat('d M y') }}</span><span>{{ $n->category?->name }}</span></div>
  @if($img ?? true)<a class="thumb" href="{{ route('news.show', $n->slug) }}" tabindex="-1">@include('partials.cover', ['news' => $n])</a>@endif
  <h3><a href="{{ route('news.show', $n->slug) }}">{{ $n->title }}</a></h3>
  <p>{{ $n->excerpt }}</p>
  <small>Kategori: {{ $n->category?->name ?? '—' }}</small>
</article>
