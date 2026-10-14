@extends('layouts.admin', ['current' => $resource])
@php use App\Admin\Resources; $title = ($model->exists ? 'Ubah ' : 'Tambah ').$res['one']; @endphp
@section('title', $title)
@section('content')
<x-admin-head :module="$resource" :title="ucfirst($title)">
  <a class="btn" href="{{ route('admin.crud.index', $resource) }}">Kembali</a>
</x-admin-head>
@if(!empty($res['simrs']))<x-sync-note title="Sebagian data dikunci">Kolom bertanda "dari SIMRS" diubah di SIMRS.</x-sync-note>@endif
<form class="card" method="post" enctype="multipart/form-data" action="{{ $model->exists ? route('admin.crud.update', [$resource, $model->getKey()]) : route('admin.crud.store', $resource) }}">
  @csrf @if($model->exists) @method('PUT') @endif
  <div class="card-b form">
  @foreach($res['fields'] as $f)
    @php $name = $f['name']; $val = old($name, $model->{$name} instanceof \Carbon\CarbonInterface ? $model->{$name}->toDateString() : $model->{$name}); @endphp
    <div class="fld {{ !empty($f['half']) ? 'half' : '' }}">
    @switch($f['type'])
      @case('readonly')
        <label>{{ $f['label'] }} @if($f['lock'] ?? true)<span class="lock">· dari SIMRS</span>@endif</label><div class="ro">{!! $f['value']($model) !!}</div>
        @break
      @case('textarea')
        <label for="f_{{ $name }}">{{ $f['label'] }}</label><textarea id="f_{{ $name }}" name="{{ $name }}" rows="{{ $f['rows'] ?? 4 }}">{{ $val }}</textarea>
        @break
      @case('select')
        <label for="f_{{ $name }}">{{ $f['label'] }}</label><select id="f_{{ $name }}" name="{{ $name }}">@foreach(($f['options'])() as $v => $l)<option value="{{ $v }}" @selected((string) $val === (string) $v)>{{ $l }}</option>@endforeach</select>
        @break
      @case('toggle')
        <div class="tog-row"><div><b>{{ $f['label'] }}</b>@isset($f['desc'])<small>{{ $f['desc'] }}</small>@endisset</div><label class="switch"><input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $model->exists ? $model->{$name} : ($f['default'] ?? false))) aria-label="{{ $f['label'] }}"><span></span></label></div>
        @break
      @case('color')
        <label>{{ $f['label'] }}</label><div class="swatches">@foreach(Resources::COLORS as $c)<label><input type="radio" name="{{ $name }}" value="{{ $c }}" @checked($val === $c) aria-label="{{ $c }}"><i style="background:{{ $c }}"></i></label>@endforeach</div>
        @break
      @case('icon')
        <label>{{ $f['label'] }}</label><div class="pick">@foreach(Resources::ICONS as $k => $path)<label title="{{ $k }}"><input type="radio" name="{{ $name }}" value="{{ $k }}" @checked($val === $k) aria-label="{{ $k }}"><svg class="i" viewBox="0 0 24 24"><path d="{{ $path }}"/></svg></label>@endforeach</div>
        @break
      @case('illustration')
        <label>{{ $f['label'] }}</label><div class="pick thumbs">@foreach(Resources::ILLUSTRATIONS as $k)<label><input type="radio" name="{{ $name }}" value="{{ $k }}" @checked($val === $k) aria-label="{{ $k }}">@include('partials.cover', ['news' => (object) ['illustration' => $k, 'image' => null]])</label>@endforeach</div>
        @break
      @case('image')
        <label for="f_{{ $name }}">{{ $f['label'] }}</label>
        @if($model->{$name})<div class="img-prev"><img src="{{ asset('storage/'.$model->{$name}) }}" alt=""><label style="font-size:13px"><input type="checkbox" name="{{ $name }}_remove" value="1"> Hapus gambar</label></div>@endif
        <input id="f_{{ $name }}" type="file" name="{{ $name }}" accept="image/png,image/jpeg,image/webp">
        @break
      @case('password')
        <label for="f_{{ $name }}">{{ $f['label'] }}</label><input id="f_{{ $name }}" type="password" name="{{ $name }}" autocomplete="new-password">
        @break
      @default
        <label for="f_{{ $name }}">{{ $f['label'] }}</label><input id="f_{{ $name }}" type="{{ $f['type'] }}" name="{{ $name }}" value="{{ $val }}">
    @endswitch
    @isset($f['hint'])<div class="hint">{{ $f['hint'] }}</div>@endisset
    @error($name)<div class="err">{{ $message }}</div>@enderror
    </div>
  @endforeach
  </div>
  <div class="save-bar"><a class="btn" href="{{ route('admin.crud.index', $resource) }}">Batal</a><button class="btn pri" type="submit">Simpan</button></div>
</form>
@endsection
