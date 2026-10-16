@extends('layouts.admin', ['current' => 'password'])
@section('title', 'Ganti kata sandi')
@section('content')
<div class="vh"><div><h1>Ganti kata sandi</h1><p>Gunakan minimal 10 karakter, gabungan huruf dan angka. Jangan memakai kata sandi yang sama dengan akun lain.</p></div></div>
<form class="card" method="post" action="{{ route('admin.password.save') }}" style="max-width:520px">
  @csrf
  <div class="card-b form">
    <div class="fld"><label for="current">Kata sandi lama</label><input id="current" type="password" name="current" autocomplete="current-password" required>@error('current')<div class="err">{{ $message }}</div>@enderror</div>
    <div class="fld"><label for="password">Kata sandi baru</label><input id="password" type="password" name="password" autocomplete="new-password" required>@error('password')<div class="err">{{ $message }}</div>@enderror</div>
    <div class="fld"><label for="password_confirmation">Ulangi kata sandi baru</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
  </div>
  <div class="save-bar"><button class="btn pri">Simpan kata sandi</button></div>
</form>
@endsection
