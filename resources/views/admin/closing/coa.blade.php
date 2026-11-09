@extends('layouts.admin', ['current' => 'closing'])
@section('title', 'COA baru · Closing')
@section('content')
@include('admin.closing._nav', ['actions' => [['Unduh COA (Excel)', route('admin.closing.export', 'coa'), false]]])
@php
  $depth = []; foreach ($book->coa as $code => $a) { $depth[$code] = $a->parent_code !== null && isset($depth[$a->parent_code]) ? $depth[$a->parent_code] + 1 : 0; }
@endphp

@if($canEdit && !$book->locked())
<form class="card" method="post" action="{{ route('admin.closing.coa.import') }}" enctype="multipart/form-data" style="margin-bottom:20px"
  data-confirm="Daftar COA akan diganti seluruhnya dengan isi file. Pemetaan akun tetap disimpan (dicocokkan dengan kode akun).">
  @csrf
  <div class="card-b form">
    <div class="fld"><label for="coaFile">Ganti COA dari file template (.xlsx)</label>
      <input id="coaFile" type="file" name="file" accept=".xlsx" required>
      <div class="hint">Format sama dengan template COA SIMRS baru: kolom <i>ID</i>, <i>Account Number</i>, <i>Account Name</i>, <i>Company</i>, <i>Parent Account</i>, <i>Is Group/Type Akun</i>, <i>Kategori Akun</i>. Hanya akun <i>Child/Detail</i> yang bisa dipakai untuk pemetaan dan jurnal.</div></div>
  </div>
  <div class="save-bar"><button class="btn pri">Unggah COA</button></div>
</form>
@endif

<div class="card">
  <div class="table-scroll"><table class="tbl cl-tbl">
    <thead><tr><th>Kode</th><th>Nama akun</th><th>Jenis</th><th>Kategori</th><th>Parent</th><th class="r">Rekening Khanza</th></tr></thead>
    <tbody>
    @forelse($book->coa as $code => $a)
      <tr class="{{ $a->type === 'header' ? 'hd' : ($a->type === 'sub' ? 'sb' : '') }}">
        <td>{{ $code }}</td><td style="padding-left:{{ 14 + $depth[$code] * 18 }}px">{{ $a->name }}</td>
        <td><span class="badge {{ $a->isDetail() ? 'b-green' : 'b-grey' }}">{{ \App\Models\ClosingAccount::TYPES[$a->type] ?? $a->type }}</span></td>
        <td>{{ $a->category }}</td><td>{{ $a->parent_code }}</td><td class="r">{{ $used[$code] ?? '' }}</td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">Belum ada COA. Unggah file template COA.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
@endsection
