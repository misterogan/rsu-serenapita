@php
  $kind = $news->illustration ?? 'heart';
  $bg = ['heart'=>['#C8332E','#E07A5F'],'child'=>['#35A3D8','#7CC6E8'],'drop'=>['#1C3B70','#35A3D8'],'phone'=>['#1F6FB2','#5FA8DC'],'sun'=>['#D08A2E','#F2C14E'],'cup'=>['#6B4A32','#A5784F'],'cross'=>['#2E8B6A','#6BBF9C']][$kind] ?? ['#1F6FB2','#35A3D8'];
  $icons = [
    'heart'=>'<path d="M200 170s-46-28-46-62a26 26 0 0 1 46-16 26 26 0 0 1 46 16c0 34-46 62-46 62z" fill="#fff"/>',
    'child'=>'<circle cx="200" cy="92" r="24" fill="#fff"/><path d="M160 180c0-30 18-50 40-50s40 20 40 50z" fill="#fff"/>',
    'drop'=>'<path d="M200 60c-30 40-46 66-46 88a46 46 0 0 0 92 0c0-22-16-48-46-88z" fill="#fff"/>',
    'phone'=>'<rect x="168" y="56" width="64" height="120" rx="10" fill="#fff"/>',
    'sun'=>'<circle cx="200" cy="110" r="34" fill="#fff"/>',
    'cup'=>'<path d="M156 90h80v48a40 40 0 0 1-80 0z" fill="#fff"/><path d="M236 104h12a16 16 0 0 1 0 32h-14" stroke="#fff" stroke-width="7" fill="none"/>',
    'cross'=>'<path d="M184 70h32v32h32v32h-32v32h-32v-32h-32v-32h32z" fill="#fff"/>',
  ];
@endphp
@if(!empty($news->image))
<img src="{{ asset('storage/'.$news->image) }}" alt="" loading="lazy">
@else
<svg viewBox="0 0 400 220" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect width="400" height="220" fill="{{ $bg[0] }}"/><rect width="400" height="220" fill="{{ $bg[1] }}" opacity=".5" transform="skewX(-20) translate(200 0)"/>{!! $icons[$kind] ?? '' !!}</svg>
@endif
