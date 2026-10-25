{{-- Banner sambutan di bagian atas dashboard. Isi slot = tombol aksi.
     <x-hero judul="..." sub="..."> <a class="btn ...">...</a> </x-hero>   warna: blue (biasa) atau abu (tidak aktif) --}}
@props(['judul', 'sub' => null, 'warna' => 'blue'])
@php
    $bg = ['blue' => 'from-navy via-flame to-sky shadow-flame/25', 'abu' => 'from-slate-700 via-slate-500 to-slate-400 shadow-slate-500/20'][$warna] ?? 'from-navy via-flame to-sky';
@endphp
<section {{ $attributes->merge(['class' => "relative overflow-hidden rounded-3xl p-6 sm:p-8 text-white bg-linear-to-br $bg shadow-lg mb-6"]) }}>
    <div class="absolute inset-0 opacity-[.08]" style="background-image: radial-gradient(#fff 1.2px, transparent 1.2px); background-size: 18px 18px"></div>
    <div class="absolute -right-12 -top-12 size-52 rounded-full bg-white/10"></div>
    <div class="absolute right-24 -bottom-14 size-36 rounded-full bg-sun/30"></div>
    <x-logo size="w-28 h-28 sm:w-36 sm:h-36" class="absolute right-5 bottom-4 hidden rotate-6 opacity-20 sm:block" />

    <div class="relative max-w-xl">
        <h2 class="font-serif text-xl sm:text-2xl font-bold leading-snug !text-white">{{ $judul }}</h2>
        @if ($sub)<p class="mt-2 text-sm sm:text-base text-white/85">{{ $sub }}</p>@endif
        @if (trim((string) $slot) !== '')
            <div class="mt-5 flex flex-wrap gap-2.5">{{ $slot }}</div>
        @endif
    </div>
</section>