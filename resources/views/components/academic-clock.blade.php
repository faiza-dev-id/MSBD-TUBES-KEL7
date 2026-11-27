@php
    $context = app(\App\Services\AcademicContextService::class)->forHeader();
    $now = now('Asia/Jakarta');
    $labelAkademik = collect([$context['semester'], $context['tahun_ajaran']])->filter()->implode(' · ');
@endphp
<div class="rounded-xl border border-[#255485] bg-[#1E4976] px-4 py-2 text-center shadow-inner backdrop-blur-sm">
    @if ($labelAkademik !== '')
        <div class="text-xs font-semibold tracking-wide text-white/90">{{ $labelAkademik }}</div>
    @endif
    <time data-waktu-real-time datetime="{{ $now->toIso8601String() }}"
          class="mt-0.5 flex items-center justify-center gap-2 text-xs text-white/75"
          aria-label="Tanggal dan waktu terkini">
        <span data-jakarta-date class="font-semibold text-white">{{ $now->locale('id')->translatedFormat('l, d F Y') }}</span>
        <span aria-hidden="true" class="h-3 border-l border-white/35"></span>
        <span data-jakarta-time class="font-semibold text-white">{{ $now->format('H.i') }}</span>
    </time>
</div>
