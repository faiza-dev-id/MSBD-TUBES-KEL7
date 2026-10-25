@props(['size' => 'w-10 h-10'])
@php
    $path = config('sekolah.logo', 'images/logo.png');
    $ada  = is_file(public_path($path));
@endphp
@if ($ada)
    <img src="{{ asset($path) }}" alt="Logo sekolah"
         {{ $attributes->merge(['class' => "$size shrink-0 object-contain"]) }}>
@else
    <div {{ $attributes->merge(['class' => "$size shrink-0 rounded-full bg-flame grid place-items-center font-serif font-bold text-white"]) }}>M</div>
@endif