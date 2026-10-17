<div class="mg-h"><h2>{{ $title }}</h2>@isset($csv)<a href="{{ route('admin.management.export', [$csv] + $pq) }}">Unduh CSV</a>@endisset</div>
@isset($note)<p class="mg-note">{!! $note !!}</p>@endisset
