{{-- Bilah kemajuan. Warna otomatis: hijau (80% ke atas), kuning (50-79), merah-jingga (di bawah 50). --}}
@props(['nilai', 'label', 'ket' => null])
@php
    $w = max(0, min(100, (int) $nilai));
    [$grad, $txt] = $w >= 80 ? ['from-leaf to-emerald-400', 'text-leaf']
                  : ($w >= 50 ? ['from-sun to-amber-300', 'text-sun-dark'] : ['from-coral to-orange-400', 'text-coral']);
@endphp
<div class="mb-4 text-sm last:mb-0">
    <div class="flex items-baseline justify-between gap-3">
        <b class="truncate">{{ $label }}</b>
        <span class="shrink-0 text-xs text-mute">@if ($ket){{ $ket }} · @endif<b class="{{ $txt }}">{{ $w }}%</b></span>
    </div>
    <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-line">
        <div class="h-full rounded-full bg-linear-to-r {{ $grad }} transition-all duration-700" style="width: {{ $w }}%"></div>
    </div>
</div>