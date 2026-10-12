@extends('layouts.admin', ['current' => 'roles'])
@php use App\Admin\Modules; use App\Models\Role; @endphp
@section('title', 'Hak akses')
@section('content')
<x-admin-head module="roles" desc="Centang menu backoffice yang boleh dibuka setiap peran. Administrator selalu punya akses penuh.">
  <form method="post" action="{{ route('admin.roles.store') }}" class="inline" style="display:flex;gap:8px">@csrf<input name="name" placeholder="Nama peran baru" aria-label="Nama peran baru" required style="padding:9px 11px;border:1px solid var(--line);border-radius:5px;background:var(--surface)"><button class="btn">+ Tambah peran</button></form>
</x-admin-head>
<form method="post" action="{{ route('admin.roles.update') }}">@csrf @method('PUT')
<div class="card"><div class="table-scroll"><table class="tbl mx"><thead><tr><th>Menu</th>
  @foreach($roles as $r)<th>{{ $r->name }}<span class="sub">{{ $r->users_count }} pengguna</span></th>@endforeach</tr></thead><tbody>
  @foreach(Modules::GROUPS as $g => $mods)
    <tr><td colspan="{{ $roles->count() + 1 }}" style="background:var(--soft);color:var(--blue);font-weight:700;text-align:left">{{ $g }}</td></tr>
    @foreach($mods as $k => $n)
    <tr><td>{{ $n }}</td>@foreach($roles as $r)<td><input type="checkbox" name="perm[{{ $r->id }}][{{ $k }}]" value="1" @checked($r->allows($k)) @disabled($r->name === Role::ADMIN) aria-label="{{ $r->name }}: {{ $n }}"></td>@endforeach</tr>
    @endforeach
  @endforeach
  <tr><td></td>@foreach($roles as $r)<td>@if($r->name !== Role::ADMIN && !$r->users_count)<button type="submit" form="del{{ $r->id }}" class="btn sm">Hapus peran</button>@endif</td>@endforeach</tr>
  </tbody></table></div>
  <div class="save-bar"><button class="btn pri">Simpan hak akses</button></div></div>
</form>
@foreach($roles as $r)<form id="del{{ $r->id }}" method="post" action="{{ route('admin.roles.destroy', $r) }}" data-confirm="Peran {{ $r->name }} akan dihapus.">@csrf @method('DELETE')</form>@endforeach
@endsection
