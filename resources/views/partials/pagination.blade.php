@if ($paginator->hasPages())
<nav class="pager" aria-label="Halaman">
  @if ($paginator->onFirstPage())<span class="off">Sebelumnya</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>@endif
  @foreach ($elements as $element)
    @if (is_string($element))<span class="off">{{ $element }}</span>@endif
    @if (is_array($element))
      @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())<span class="on" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
      @endforeach
    @endif
  @endforeach
  @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>@else<span class="off">Berikutnya</span>@endif
</nav>
@endif
