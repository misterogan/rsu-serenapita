@extends('layouts.admin', ['current' => 'closing'])
@section('title', ($adj->exists ? 'Ubah ' : 'Buat ').'jurnal penyesuaian · Closing')
@section('content')
@include('admin.closing._nav', ['title' => ($adj->exists ? 'Ubah jurnal ' : 'Jurnal penyesuaian baru ').$adj->number])
@php
  $s = $book->settings;
  $lines = old('lines', $lines);
@endphp
<datalist id="coaList">@foreach($book->coa->filter->isDetail() as $code => $a)<option value="{{ $code }}">{{ $code }} · {{ $a->name }}</option>@endforeach</datalist>
<script type="application/json" id="coaNames">{!! json_encode($book->coa->filter->isDetail()->map(fn ($a) => $a->name), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

<form method="post" action="{{ $adj->exists ? route('admin.closing.adjustments.update', $adj) : route('admin.closing.adjustments.store') }}" class="card" id="adjForm">
  @csrf @if($adj->exists) @method('PUT') @endif
  <div class="card-b form">
    <div class="fld half"><label for="adjDate">Tanggal</label><input id="adjDate" type="date" name="date" value="{{ old('date', $adj->date?->toDateString()) }}" min="{{ $s['year_start'] }}" max="{{ $s['cutoff'] }}" required>
      <div class="hint">Antara awal tahun buku dan tanggal cut off. Menentukan bulan pada laporan laba rugi per bulan.</div></div>
    <div class="fld"><label>Jenis jurnal</label><div class="tog-list">
      @foreach(\App\Models\ClosingAdjustment::KINDS as $k => $label)<label class="tog-row"><input type="radio" name="kind" value="{{ $k }}" @checked(old('kind', $adj->kind ?? 'penyesuaian') === $k)><div><b>{{ $label }}</b>
        <small>{{ $k === 'koreksi' ? 'Hanya untuk selisih neraca saldo yang berasal dari Khanza. Total semua koreksi seharusnya menghapus selisih di menu Neraca saldo.' : 'Untuk koreksi salah akun, selisih rekonsiliasi, penyusutan, pendapatan belum ditagih, dan sejenisnya.' }}</small></div></label>@endforeach
    </div></div>
    <div class="fld half"><label for="adjDesc">Keterangan</label><input id="adjDesc" type="text" name="description" value="{{ old('description', $adj->description) }}" maxlength="250" required placeholder="Contoh: Koreksi piutang BPJS sesuai rincian per cut off"></div>
  </div>
  <div class="table-scroll"><table class="mg-tbl cl-tbl" style="margin:0 20px;width:calc(100% - 40px)">
    <thead><tr><th>Akun COA</th><th>Nama akun</th><th>Memo</th><th class="r">Debet</th><th class="r">Kredit</th><th></th></tr></thead>
    <tbody id="adjLines">
    @foreach($lines as $i => $l)
      <tr>
        <td><input class="coa" name="lines[{{ $i }}][coa_code]" value="{{ $l['coa_code'] ?? '' }}" list="coaList" autocomplete="off" aria-label="Akun"></td>
        <td class="coa-name" style="color:var(--muted);font-size:13px">{{ $book->account($l['coa_code'] ?? null)?->name }}</td>
        <td><input class="memo" name="lines[{{ $i }}][memo]" value="{{ $l['memo'] ?? '' }}" maxlength="200" aria-label="Memo"></td>
        <td class="r"><input class="num" type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" value="{{ ($l['debit'] ?? 0) ?: '' }}" aria-label="Debet"></td>
        <td class="r"><input class="num" type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" value="{{ ($l['credit'] ?? 0) ?: '' }}" aria-label="Kredit"></td>
        <td><button type="button" class="ibtn del" data-del title="Hapus baris" aria-label="Hapus baris"><svg class="i" viewBox="0 0 24 24"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></button></td>
      </tr>
    @endforeach
    </tbody>
    <tfoot><tr class="tot"><td colspan="3"><button type="button" class="btn sm" id="adjAdd">+ Tambah baris</button> <span id="adjBal" style="margin-left:10px"></span></td><td class="r" id="adjTd"></td><td class="r" id="adjTc"></td><td></td></tr></tfoot>
  </table></div>
  <div class="save-bar"><a class="btn" href="{{ route('admin.closing.adjustments') }}">Batal</a><button class="btn pri">Simpan jurnal</button></div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  const body = document.getElementById("adjLines"), names = JSON.parse(document.getElementById("coaNames").textContent);
  const fmt = v => v.toLocaleString("id-ID", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const sum = () => {
    let d = 0, c = 0;
    body.querySelectorAll("tr").forEach(tr => { d += +tr.querySelector("[name$='[debit]']").value || 0; c += +tr.querySelector("[name$='[credit]']").value || 0; });
    document.getElementById("adjTd").textContent = fmt(d); document.getElementById("adjTc").textContent = fmt(c);
    const diff = Math.round((d - c) * 100) / 100, el = document.getElementById("adjBal");
    const corr = document.querySelector("[name=kind][value=koreksi]").checked;
    el.innerHTML = diff ? '<span class="tag ' + (corr ? 'warn' : 'bad') + '">' + (corr ? 'Koreksi sepihak: ' : 'Belum seimbang: ') + fmt(diff) + '</span>' : (d ? '<span class="tag ok">Seimbang</span>' : '');
  };
  let n = body.children.length + 100;
  document.getElementById("adjAdd").addEventListener("click", () => {
    const tr = body.lastElementChild.cloneNode(true), i = n++;
    tr.querySelectorAll("input").forEach(inp => { inp.value = ""; inp.name = inp.name.replace(/lines\[\d+\]/, "lines[" + i + "]"); });
    tr.querySelector(".coa-name").textContent = ""; body.appendChild(tr); tr.querySelector("input.coa").focus();
  });
  body.addEventListener("click", e => { if (e.target.closest("[data-del]") && body.children.length > 2) { e.target.closest("tr").remove(); sum(); } });
  body.addEventListener("input", e => {
    if (e.target.matches("input.coa")) e.target.closest("tr").querySelector(".coa-name").textContent = names[e.target.value.trim()] || (e.target.value.trim() ? "Kode tidak ada di COA" : "");
    sum();
  });
  document.querySelectorAll("[name=kind]").forEach(r => r.addEventListener("change", sum));
  sum();
})();
</script>
@endpush
