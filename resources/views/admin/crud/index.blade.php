@extends('layouts.admin', ['current' => $resource])
@section('title', $res['title'])
@section('content')
<x-admin-head :module="$resource" :title="$res['title']" :desc="$res['desc'] ?? null">
  @if(!empty($res['simrs']) && auth()->user()->canModule('simrs'))
    <form method="post" action="{{ route('admin.simrs.sync') }}" class="inline">@csrf<button class="btn">Sinkronkan sekarang</button></form>
  @endif
  @unless($res['noCreate'] ?? false)<a class="btn pri" href="{{ route('admin.crud.create', $resource) }}">+ Tambah {{ $res['one'] }}</a>@endunless
</x-admin-head>
@if(!empty($res['simrs']))<x-sync-note />@endif
<div class="card">
  <form class="list-tools" method="get" data-noload>
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari {{ $res['one'] }}" aria-label="Cari {{ $res['one'] }}">
    @foreach($res['filters'] ?? [] as $field => $f)
      @if(($f['type'] ?? null) === 'date')
        <input type="date" name="{{ $field }}" value="{{ request($field) }}" aria-label="{{ $f['label'] }}" onchange="this.form.submit()">
      @else
        <select name="{{ $field }}" aria-label="{{ $f['label'] }}" onchange="this.form.submit()"><option value="">{{ $f['label'] }}</option>
          @foreach(($f['options'])() as $v => $l)<option value="{{ $v }}" @selected((string) request($field) === (string) $v)>{{ $l }}</option>@endforeach</select>
      @endif
    @endforeach
    <button class="btn sm">Terapkan</button>
    <span class="count">{{ $rows->total() }} dari {{ $total }} data</span>
  </form>
  <div class="table-scroll"><table class="tbl">
    <thead><tr>@foreach($res['columns'] as $h => $c)<th>{{ $h }}</th>@endforeach<th></th></tr></thead>
    <tbody>
    @forelse($rows as $r)
      <tr>
        @foreach($res['columns'] as $c)<td>{!! $c($r) !!}</td>@endforeach
        <td class="acts">
          @if(($res['sortable'] ?? false) && !request('q'))
            <form method="post" class="inline" action="{{ route('admin.crud.move', [$resource, $r->getKey(), 'up']) }}">@csrf<button class="ibtn" title="Naikkan" aria-label="Naikkan" @disabled($loop->first && $rows->onFirstPage())><svg class="i" viewBox="0 0 24 24"><path d="M12 19V5M6 11l6-6 6 6"/></svg></button></form>
            <form method="post" class="inline" action="{{ route('admin.crud.move', [$resource, $r->getKey(), 'down']) }}">@csrf<button class="ibtn" title="Turunkan" aria-label="Turunkan" @disabled($loop->last && !$rows->hasMorePages())><svg class="i" viewBox="0 0 24 24"><path d="M12 5v14M6 13l6 6 6-6"/></svg></button></form>
          @endif
          @unless($res['noEdit'] ?? false)<a class="ibtn" href="{{ route('admin.crud.edit', [$resource, $r->getKey()]) }}" title="Ubah" aria-label="Ubah"><svg class="i" viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16zM14 6l4 4"/></svg></a>@endunless
          @unless($res['noDelete'] ?? false)
            <form method="post" class="inline" action="{{ route('admin.crud.destroy', [$resource, $r->getKey()]) }}" data-confirm="{{ ucfirst($res['one']) }} &quot;{{ isset($res['label']) ? $res['label']($r) : $r->getKey() }}&quot; akan dihapus permanen.">@csrf @method('DELETE')
              <button class="ibtn del" title="Hapus" aria-label="Hapus"><svg class="i" viewBox="0 0 24 24"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></button></form>
          @endunless
        </td>
      </tr>
    @empty
      <tr><td colspan="{{ count($res['columns']) + 1 }}"><div class="empty">{{ request()->hasAny(['q', 'status', 'date']) ? 'Tidak ada data yang cocok dengan pencarian atau filter.' : (($res['noCreate'] ?? false) ? 'Belum ada data dari SIMRS.' : 'Belum ada '.$res['one'].'.') }}</div></td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $rows->links('partials.pagination') }}
</div>
@endsection
