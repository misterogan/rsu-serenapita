@extends('layouts.admin', ['current' => 'closing'])
@use('App\Support\Fmt', 'F')
@section('title', 'Jurnal penyesuaian · Closing')
@section('content')
@include('admin.closing._nav', ['actions' => [['Unduh jurnal (Excel)', route('admin.closing.export', 'penyesuaian'), false]]])
@php $edit = $canEdit && !$book->locked(); @endphp
<div class="stk-note">Jurnal penyesuaian dan koreksi untuk merapikan saldo Khanza sebelum menjadi saldo awal SIMRS baru: misalnya koreksi salah posting, selisih rekonsiliasi, penyusutan yang belum dicatat, pendapatan yang belum ditagih, atau reklasifikasi akun.
  Memakai akun COA baru, bertanggal di dalam periode, dan tidak mengubah data di Khanza.
  @if($edit)<a class="btn sm pri" style="margin-left:8px" href="{{ route('admin.closing.adjustments.create') }}">Buat jurnal</a>@endif</div>

@forelse($items as $a)
  <div class="card mg-card">
    <div class="mg-h"><h2>{{ $a->number }} · {{ $a->date->translatedFormat('d M Y') }} @if($a->isCorrection())<span class="badge b-amber">Koreksi selisih Khanza</span>@endif</h2>
      <span>@if($edit)<a class="btn sm" href="{{ route('admin.closing.adjustments.edit', $a) }}">Ubah</a>
        <form method="post" action="{{ route('admin.closing.adjustments.destroy', $a) }}" class="inline" data-confirm="Hapus jurnal {{ $a->number }}?">@csrf @method('DELETE')<button class="btn sm danger">Hapus</button></form>@endif</span></div>
    <p class="mg-note">{{ $a->description }} · dibuat {{ $a->user_name }}, {{ $a->updated_at->translatedFormat('d M Y H:i') }}</p>
    <table class="mg-tbl cl-tbl"><thead><tr><th>Akun</th><th>Memo</th><th class="r">Debet</th><th class="r">Kredit</th></tr></thead><tbody>
    @foreach($a->lines as $l)<tr><td><b>{{ $l->coa_code }}</b> {{ $book->account($l->coa_code)?->name ?? '(tidak ada di COA)' }}</td><td>{{ $l->memo }}</td>
      <td class="r">{{ $l->debit ? F::n($l->debit, 2) : '' }}</td><td class="r">{{ $l->credit ? F::n($l->credit, 2) : '' }}</td></tr>@endforeach
    <tr class="tot"><td colspan="2">Total</td><td class="r">{{ F::n($a->lines->sum('debit'), 2) }}</td><td class="r">{{ F::n($a->lines->sum('credit'), 2) }}</td></tr>
    </tbody></table>
  </div>
@empty
  <div class="card mg-card"><p class="mg-empty">Belum ada jurnal penyesuaian.</p></div>
@endforelse
@endsection
