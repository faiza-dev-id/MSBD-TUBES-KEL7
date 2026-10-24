@props(['size' => 'w-10 h-10'])
@php
    $path = config('sekolah.logo', 'images/logo.jpg');
    $ada  = is_file(public_path($path));
@endphp
@if ($ada)
    <img src="{{ asset($path) }}" alt="Logo sekolah"
         {{ $attributes->merge(['class' => "$size shrink-0 object-contain bg-white rounded-lg p-1"]) }}>
@else
    <div {{ $attributes->merge(['class' => "$size shrink-0 rounded-full bg-flame grid place-items-center font-serif font-bold text-white"]) }}>M</div>
@endif