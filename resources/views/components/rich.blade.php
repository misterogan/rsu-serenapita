@props(['name', 'label' => null, 'value' => '', 'hint' => null, 'rows' => 6, 'id' => null])
@php $fid = $id ?? 'f_'.str_replace(['[', ']'], '_', $name); $html = \App\Support\Rich::toEditor($value); @endphp
@if($label)<label for="{{ $fid }}_area">{{ $label }}</label>@endif
<div class="rte" data-rte>
  <div class="rte-bar" role="toolbar" aria-label="Format teks {{ $label }}">
    <button type="button" data-cmd="bold" title="Tebal (Ctrl+B)" aria-label="Tebal"><b>B</b></button>
    <button type="button" data-cmd="italic" title="Miring (Ctrl+I)" aria-label="Miring"><i>I</i></button>
    <button type="button" data-cmd="underline" title="Garis bawah (Ctrl+U)" aria-label="Garis bawah"><u>U</u></button>
    <span class="rte-sep"></span>
    <button type="button" data-cmd="h3" title="Subjudul" aria-label="Subjudul">H</button>
    <button type="button" data-cmd="insertUnorderedList" title="Daftar poin" aria-label="Daftar poin"><svg viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1.4"/><circle cx="4.5" cy="12" r="1.4"/><circle cx="4.5" cy="18" r="1.4"/></svg></button>
    <button type="button" data-cmd="insertOrderedList" title="Daftar bernomor" aria-label="Daftar bernomor"><svg viewBox="0 0 24 24"><path d="M10 6h10M10 12h10M10 18h10"/><text x="2" y="8.5" font-size="7" font-weight="700">1</text><text x="2" y="14.5" font-size="7" font-weight="700">2</text><text x="2" y="20.5" font-size="7" font-weight="700">3</text></svg></button>
    <span class="rte-sep"></span>
    <button type="button" data-cmd="link" title="Tautan" aria-label="Tautan"><svg viewBox="0 0 24 24"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg></button>
    <button type="button" data-cmd="removeFormat" title="Hapus format" aria-label="Hapus format"><svg viewBox="0 0 24 24"><path d="M6 5h12M12 5l-3 14M4 20l16-16"/></svg></button>
  </div>
  <div class="rte-area" id="{{ $fid }}_area" contenteditable="true" role="textbox" aria-multiline="true" style="min-height:{{ max(3, (int) $rows) * 1.65 }}em">{!! $html !!}</div>
  <textarea id="{{ $fid }}" name="{{ $name }}" hidden>{{ $html }}</textarea>
</div>
@if($hint)<div class="hint">{{ $hint }}</div>@endif
