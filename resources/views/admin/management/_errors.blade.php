@if(!empty($errors_))
<details class="flash err mg-errs"><summary>{{ count($errors_) }} laporan di bagian ini tidak bisa dibaca dari database Khanza. Klik untuk detail (untuk tim IT).</summary>
  <ul>@foreach($errors_ as $k => $m)<li><b>{{ $k }}</b>: {{ $m }}</li>@endforeach</ul></details>
@endif
