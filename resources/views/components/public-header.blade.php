@php
    $menu = [
        ['label' => 'Beranda', 'route' => 'home', 'active' => 'home'],
        ['label' => 'Daftar PPDB', 'route' => 'ppdb', 'active' => 'ppdb'],
        ['label' => 'Cek Status', 'route' => 'ppdb.status', 'active' => 'ppdb.status*'],
    ];
@endphp
<header class="sticky top-0 z-40 bg-navy text-white shadow-md">
    <div class="relative mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 lg:px-8">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
            <x-logo size="w-10 h-10" />
            <span class="min-w-0 leading-tight">
                <span class="block truncate font-serif text-sm font-semibold">{{ config('sekolah.nama') }}</span>
                <span class="block truncate text-[11px] text-white/75">{{ config('sekolah.sistem') }}</span>
            </span>
        </a>

        <div class="absolute left-1/2 hidden -translate-x-1/2 xl:block">
            <x-academic-clock />
        </div>

        <nav class="ml-auto hidden items-center gap-1 text-sm font-medium md:flex" aria-label="Menu utama">
            @foreach ($menu as $item)
                <a href="{{ route($item['route']) }}"
                   @if (request()->routeIs($item['active'])) aria-current="page" @endif
                   class="rounded-lg px-3 py-2 transition {{ request()->routeIs($item['active']) ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
            <a href="{{ route('login') }}" class="ml-1 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-navy transition hover:bg-tint">
                Masuk
            </a>
        </nav>

        <button type="button" id="burger"
                class="ml-auto grid size-10 place-items-center rounded-lg border border-white/25 text-white md:hidden"
                aria-label="Buka menu" aria-expanded="false" aria-controls="menu-mobile">
            <x-icon name="menu" size="w-5 h-5" />
        </button>
    </div>

    <nav id="menu-mobile" class="hidden border-t border-white/15 bg-navy px-4 py-3 md:hidden" aria-label="Menu seluler">
        @foreach ($menu as $item)
            <a href="{{ route($item['route']) }}"
               class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs($item['active']) ? 'bg-white/15 text-white' : 'text-white/80' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
        <a href="{{ route('login') }}" class="mt-2 block rounded-lg bg-white px-3 py-2.5 text-sm font-semibold text-navy">Masuk</a>
    </nav>
</header>

@once
    @push('scripts')
        <script>
            (() => {
                const button = document.getElementById('burger');
                const menu = document.getElementById('menu-mobile');
                if (!button || !menu) return;
                button.addEventListener('click', () => {
                    const open = menu.classList.toggle('hidden') === false;
                    button.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            })();
        </script>
    @endpush
@endonce
