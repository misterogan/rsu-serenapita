@extends('layouts.admin', ['current' => 'logs'])
@section('title', 'Log aktivitas')
@section('content')
<x-admin-head module="logs" desc="Riwayat perubahan oleh petugas." />
<div class="card">
  <form class="list-tools" method="get" data-noload><input type="search" name="q" value="{{ request('q') }}" placeholder="Cari aktivitas atau nama petugas" aria-label="Cari log"><button class="btn sm">Cari</button><span class="count">{{ $logs->total() }} entri</span></form>
  <div class="table-scroll"><table class="tbl"><thead><tr><th>Waktu</th><th>Petugas</th><th>Aktivitas</th></tr></thead><tbody>
  @forelse($logs as $l)<tr><td style="white-space:nowrap">{{ $l->created_at->translatedFormat('d M Y H.i') }}</td><td>{{ $l->user_name }}</td><td>{{ $l->action }}</td></tr>
  @empty<tr><td colspan="3"><div class="empty">Tidak ada aktivitas yang cocok.</div></td></tr>@endforelse
  </tbody></table></div>
  {{ $logs->links('partials.pagination') }}
</div>
@endsection
