@extends('layouts.landing')

@section('title', 'Beranda')

@php
    // Ikon garis sederhana untuk strip statistik.
    $ikon = [
        'gedung' => 'M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1M9 13h1m4 0h1M9 17h6',
        'buku'   => 'M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2zM22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z',
        'piala'  => 'M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3',
        'siswa'  => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    ];
    $banyak = count($slides);
@endphp

@section('content')
<section data-slider data-reveal-up class="relative isolate overflow-hidden bg-navy text-white"
         aria-roledescription="carousel" aria-label="Sorotan sekolah">
    <h1 class="sr-only">{{ config('sekolah.nama') }}</h1>

    {{-- Area slide --}}
    <div class="relative min-h-[30rem] sm:min-h-[34rem] lg:min-h-[38rem]">
        @foreach ($slides as $i => $s)
            <article data-slide
                     class="absolute inset-0 transition-opacity duration-700 motion-reduce:transition-none {{ $i === 0 ? 'opacity-100' : 'pointer-events-none opacity-0' }}"
                     role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} dari {{ $banyak }}"
                     aria-hidden="{{ $i === 0 ? 'false' : 'true' }}">

                @if ($s['gambar'])
                    <img data-latar-foto src="{{ asset($s['gambar']) }}" alt="" class="absolute inset-0 size-full object-cover"
                         @if ($i > 0) loading="lazy" @endif>
                @else
                    <div class="absolute inset-0 bg-linear-to-br from-navy via-navy-soft to-flame"></div>
                @endif
                <div class="absolute inset-0 bg-linear-to-r from-navy/95 via-navy/75 to-navy/25"></div>

                <div class="relative mx-auto flex h-full max-w-7xl items-center px-14 pb-16 sm:px-20 lg:px-24">
                    <div class="max-w-2xl py-12">
                        @if ($i === 0 && ($akreditasi || $npsn))
                            <span class="mb-5 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs ring-1 ring-white/25">
                                <span class="size-1.5 rounded-full bg-sun"></span>
                                @if ($akreditasi) Terakreditasi {{ $akreditasi }} @endif
                                @if ($akreditasi && $npsn) &middot; @endif
                                @if ($npsn) NPSN {{ $npsn }} @endif
                            </span>
                        @endif

                        <h2 data-reveal-words class="font-serif text-3xl font-bold leading-[1.12] !text-white sm:text-4xl lg:text-5xl">
                            {{ $s['awal'] }}
                            <em class="font-normal text-sky">{{ $s['miring'] }}</em>{{ $s['akhir'] }}
                        </h2>

                        <p data-reveal-up class="mt-5 max-w-lg text-sm leading-relaxed text-white/85 sm:text-base">{{ $s['deskripsi'] }}</p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            @foreach ($s['tombol'] as $t)
                                <a href="{{ $t['url'] }}"
                                   class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white
                                          {{ $loop->first ? 'bg-white text-navy hover:bg-tint' : 'bg-white/10 text-white ring-1 ring-white/30 hover:bg-white/20' }}">
                                    {{ $t['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>
        @endforeach

        @if ($banyak > 1)
            <button type="button" data-prev aria-label="Slide sebelumnya"
                    class="absolute left-3 top-1/2 z-10 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-black/30 ring-1 ring-white/30 backdrop-blur transition hover:bg-black/50 focus-visible:outline-2 focus-visible:outline-white sm:left-5">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <button type="button" data-next aria-label="Slide berikutnya"
                    class="absolute right-3 top-1/2 z-10 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-black/30 ring-1 ring-white/30 backdrop-blur transition hover:bg-black/50 focus-visible:outline-2 focus-visible:outline-white sm:right-5">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
            </button>

            <div class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-2">
                @foreach ($slides as $i => $s)
                    <button type="button" data-dot aria-label="Ke slide {{ $i + 1 }}"
                            class="h-2 rounded-full transition-all {{ $i === 0 ? 'w-6 bg-white' : 'w-2 bg-white/40' }}"></button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Strip statistik --}}
    <div class="border-t border-white/10 bg-navy/90">
        <dl class="mx-auto flex max-w-7xl flex-wrap justify-center gap-x-12 gap-y-4 px-4 py-4 text-sm lg:px-8">
            @foreach ($statistik as $st)
                <div data-motion-card data-reveal-up class="flex w-[calc(50%-1.5rem)] items-center gap-3 text-white/90 sm:w-auto">
                    <svg class="size-5 shrink-0 text-sky" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="{{ $ikon[$st['ikon']] ?? $ikon['buku'] }}"/>
                    </svg>
                    <div class="flex items-baseline gap-1.5">
                        <dt class="sr-only">{{ $st['label'] }}</dt>
                        <dd>{{ $st['nilai'] }} <span class="text-white/70">{{ $st['label'] }}</span></dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </div>
</section>
@endsection

@push('scripts')
<script>
    (() => {
        const root = document.querySelector('[data-slider]');
        if (!root) return;
        const slides = [...root.querySelectorAll('[data-slide]')];
        const dots = [...root.querySelectorAll('[data-dot]')];
        if (slides.length < 2) return;

        let aktif = 0;
        let timer = null;
        const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function tampil(n) {
            aktif = (n + slides.length) % slides.length;
            slides.forEach((s, k) => {
                const on = k === aktif;
                s.classList.toggle('opacity-100', on);
                s.classList.toggle('opacity-0', !on);
                s.classList.toggle('pointer-events-none', !on);
                s.setAttribute('aria-hidden', on ? 'false' : 'true');
            });
            dots.forEach((d, k) => {
                const on = k === aktif;
                d.classList.toggle('w-6', on);
                d.classList.toggle('bg-white', on);
                d.classList.toggle('w-2', !on);
                d.classList.toggle('bg-white/40', !on);
                d.setAttribute('aria-current', on ? 'true' : 'false');
            });
        }

        function mulai() {
            if (kurangiGerak) return;
            berhenti();
            timer = setInterval(() => tampil(aktif + 1), 6000);
        }
        function berhenti() { clearInterval(timer); }

        root.querySelector('[data-prev]')?.addEventListener('click', () => { tampil(aktif - 1); mulai(); });
        root.querySelector('[data-next]')?.addEventListener('click', () => { tampil(aktif + 1); mulai(); });
        dots.forEach((d, k) => d.addEventListener('click', () => { tampil(k); mulai(); }));

        root.addEventListener('mouseenter', berhenti);
        root.addEventListener('mouseleave', mulai);
        root.addEventListener('focusin', berhenti);
        root.addEventListener('focusout', mulai);

        mulai();
    })();
</script>
@endpush