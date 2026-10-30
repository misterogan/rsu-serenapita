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
      @case('rich')
        <x-rich :name="$name" :label="$f['label']" :value="$val" :rows="$f['rows'] ?? 6" />
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
      @case('schedule')
        @php
          $rows = collect(is_array($val) ? $val : [])->map(fn ($r) => isset($r['day']) || array_key_exists('day', (array) $r) ? ['day' => $r['day'] ?? '', 'start' => str_replace('.', ':', $r['start'] ?? ''), 'end' => str_replace('.', ':', $r['end'] ?? '')] : ['day' => $r[0], 'start' => str_replace('.', ':', $r[1]), 'end' => str_replace('.', ':', $r[2])])->values();
          if ($rows->isEmpty()) { $rows = collect([['day' => '', 'start' => '', 'end' => '']]); }
          $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
        @endphp
        <label>{{ $f['label'] }}</label>
        <div class="table-scroll"><table class="tbl sched-edit" style="min-width:0"><thead><tr><th>Hari</th><th>Jam mulai</th><th>Jam selesai</th><th></th></tr></thead>
          <tbody data-sched-rows>
          @foreach($rows as $i => $r)
            <tr>
              <td><select name="{{ $name }}[{{ $i }}][day]" aria-label="Hari"><option value="">Pilih hari</option>@foreach($dayNames as $dv => $dl)<option value="{{ $dv }}" @selected((string) $r['day'] === (string) $dv)>{{ $dl }}</option>@endforeach</select></td>
              <td><input type="time" name="{{ $name }}[{{ $i }}][start]" value="{{ $r['start'] }}" aria-label="Jam mulai"></td>
              <td><input type="time" name="{{ $name }}[{{ $i }}][end]" value="{{ $r['end'] }}" aria-label="Jam selesai"></td>
              <td><button type="button" class="ibtn del" data-sched-del title="Hapus baris" aria-label="Hapus baris"><svg class="i" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg></button></td>
            </tr>
          @endforeach
          </tbody></table></div>
        <button type="button" class="btn sm" data-sched-add style="margin-top:8px">+ Tambah jadwal</button>
        <script>
        (function () {
          const tb = document.querySelector("[data-sched-rows]"); if (!tb || tb.dataset.ready) return; tb.dataset.ready = 1;
          const reindex = () => [...tb.rows].forEach((tr, i) => tr.querySelectorAll("[name]").forEach(el => el.name = el.name.replace(/\[\d+\]/, "[" + i + "]")));
          document.querySelector("[data-sched-add]").addEventListener("click", () => {
            const last = tb.rows[tb.rows.length - 1], tr = last.cloneNode(true);
            const prevDay = last.querySelector("select").value;
            tr.querySelectorAll("input").forEach(el => el.value = "");
            tr.querySelector("select").value = prevDay === "" ? "" : String((+prevDay + 1) % 7);
            tb.appendChild(tr); reindex(); tr.querySelector("input").focus();
          });
          tb.addEventListener("click", e => {
            const b = e.target.closest("[data-sched-del]"); if (!b) return;
            const tr = b.closest("tr");
            if (tb.rows.length > 1) tr.remove(); else tr.querySelectorAll("select,input").forEach(el => el.value = "");
            reindex();
          });
        })();
        </script>
        @break
      @case('password')
        <label for="f_{{ $name }}">{{ $f['label'] }}</label><input id="f_{{ $name }}" type="password" name="{{ $name }}" autocomplete="new-password">
        @break
      @default
        @php if (isset($f['hidePrefix']) && str_starts_with((string) $val, $f['hidePrefix'])) { $val = ''; } @endphp
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
