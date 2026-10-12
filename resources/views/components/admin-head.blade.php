@props(['module', 'title' => null, 'desc' => null])
<div class="vh">
  <div>
    <div class="crumb">{{ \App\Admin\Modules::group($module) }} / {{ \App\Admin\Modules::name($module) }}</div>
    <h1>{{ $title ?? \App\Admin\Modules::name($module) }}</h1>
    @if($desc)<p>{{ $desc }}</p>@endif
  </div>
  <div class="vh-act">{{ $slot }}</div>
</div>
