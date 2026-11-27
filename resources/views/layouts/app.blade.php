@php
    $role = session('role');
    // Menu "Ganti Sandi" hanya untuk role di config/sekolah.php ('role_ganti_sandi')
    $akun = in_array($role, config('sekolah.role_ganti_sandi'), true) ? ['Akun', [['Ganti Sandi', 'akun.sandi']]] : null;
    $kelasMenu = ['Kelas', [['Daftar Kelas & Wali', 'tu.kelas'], ['Kenaikan Kelas', 'tu.kenaikan']]];
    $akademikMenu = ['Akademik', [['Konfigurasi Akademik', 'akademik.konfigurasi'], ['Jadwal Pelajaran', 'akademik.jadwal']]];

    $menu = match ($role) {
        'siswa'  => [['Utama', [['Beranda', 'siswa.dashboard']]], ['Akademik', [['Jadwal Pelajaran', 'siswa.jadwal'], ['Kehadiran', 'siswa.absensi'], ['Rapor Siswa', 'siswa.rapor']]], ['Data Saya', [['Profil Saya', 'siswa.profil'], ['Konfirmasi Data', 'siswa.konfirmasi'], ['Ubah Data', 'siswa.pengajuan']]], $akun],
        'wali_kelas' => [['Wali Kelas', [['Daftar Siswa Kelas', 'guru.kelas'], ['Kelengkapan Data', 'guru.kelengkapan'], ['Validasi Pengajuan', 'guru.pengajuan'], ['Catatan Wali Kelas', 'guru.catatan'], ['Absensi Kelas', 'guru.absensi'], ['Rapor Siswa', 'guru.rapor']]], $akun],
        'tu'     => [
            ['Utama', [['Meja TU', 'tu.dashboard']]],
            ['PPDB', [['Antrean Verifikasi', 'tu.antrean']]],
            ['Data Siswa', [['Data Induk Siswa', 'tu.siswa'], ['Periode Update', 'tu.periode'], ['Reset Sandi Akun', 'tu.reset'], ['Mutasi Siswa', 'tu.mutasi']]],
            ['Data Master', [['Data Referensi', 'admin.referensi']]],
            $kelasMenu, $akademikMenu, $akun,
        ],
        'kepsek' => [
            ['Utama', [['Ringkasan', 'kepsek.dashboard'], ['Persetujuan Mutasi', 'kepsek.mutasi']]],
            ['Pengguna & Keamanan', [['Daftar Pengguna', 'admin.pengguna'], ['Wali Kelas & Staf', 'admin.staf'], ['Log Login', 'admin.log'], ['Jejak Audit', 'admin.audit'], ['Pengaturan Sistem', 'admin.pengaturan']]],
            $akun,
        ],
        default  => [],
    };

    // buang entri kosong (menu Akun untuk role yang tidak boleh ganti sandi)
    $menu = array_values(array_filter($menu));

    // ikon tiap menu (nama ikon ada di components/icon.blade.php)
    $ikon = [
        'siswa.dashboard' => 'home', 'siswa.profil' => 'idcard', 'siswa.konfirmasi' => 'check', 'siswa.pengajuan' => 'edit',
        'siswa.jadwal' => 'calendar', 'siswa.absensi' => 'check', 'siswa.rapor' => 'book',
        'guru.kelas' => 'users', 'guru.kelengkapan' => 'clipboard', 'guru.catatan' => 'book',
        'guru.pengajuan' => 'file', 'guru.absensi' => 'check', 'guru.rapor' => 'book',
        'tu.dashboard' => 'home', 'tu.antrean' => 'clipboard', 'tu.siswa' => 'folder', 'tu.periode' => 'clock', 'tu.reset' => 'key',
        'tu.mutasi' => 'trend', 'kepsek.mutasi' => 'trend',
        'tu.kelas' => 'cap', 'tu.kenaikan' => 'trend',
        'akademik.konfigurasi' => 'sliders', 'akademik.jadwal' => 'grid',
        'kepsek.dashboard' => 'chart',
        'admin.pengguna' => 'user', 'admin.staf' => 'briefcase', 'admin.log' => 'key',
        'admin.audit' => 'file', 'admin.pengaturan' => 'gear', 'admin.referensi' => 'database',
        'akun.sandi' => 'lock',
    ];

    $namaUser  = session('nama', 'Pengguna');
    $labelRole = request()->user()?->role?->nama_role ?? str_replace('_', ' ', ucfirst((string) $role));

    // Jumlah notifikasi belum dibaca (0 bila belum login atau query gagal).
    $notifBelum = 0;
    if (session('id_pengguna')) {
        try {
            $notifBelum = \App\Models\Notifikasi::where('id_pengguna', session('id_pengguna'))
                ->where('dibaca', false)
                ->count();
        } catch (\Throwable $e) {
            $notifBelum = 0;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') · {{ config('sekolah.nama') }}</title>
    <link rel="icon" href="{{ asset(config('sekolah.logo')) }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="theme-background min-h-screen" style="--theme-foto: url('{{ asset('images/sekolah.jpeg') }}')">

{{-- TOPBAR --}}
<header class="sticky top-0 z-30 bg-navy text-white shadow-md">
    <div class="relative mx-auto flex h-16 max-w-[1920px] items-center gap-3 px-4 lg:px-6">
        <button id="sbBtn" type="button" aria-label="Buka menu" class="lg:hidden -ml-1 rounded-lg p-2 hover:bg-white/10 cursor-pointer">
            <x-icon name="menu" size="w-6 h-6" />
        </button>
        <button id="sbToggle" type="button" aria-label="Tutup menu" aria-controls="sb" aria-expanded="true" class="hidden lg:inline-flex -ml-1 rounded-lg p-2 hover:bg-white/10 cursor-pointer">
            <x-icon name="menu" size="w-6 h-6" />
        </button>
        <x-logo size="w-10 h-10" />
        <div class="min-w-0 leading-tight">
            <div class="truncate font-serif font-semibold">{{ config('sekolah.nama') }}</div>
            <div class="hidden truncate text-xs text-white/75 sm:block">{{ config('sekolah.sistem') }}</div>
        </div>
        <div class="absolute left-1/2 hidden -translate-x-1/2 xl:block">
            <x-academic-clock />
        </div>
        <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
        <a href="{{ route('notifikasi') }}" aria-label="Notifikasi ({{ $notifBelum }} belum dibaca)"
           class="relative rounded-lg p-2 hover:bg-white/10 cursor-pointer">
            <x-icon name="bell" size="w-6 h-6" />
            @if ($notifBelum > 0)
                <span class="absolute -top-0.5 -right-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-bad px-1 text-[10px] font-bold text-white ring-2 ring-navy">
                    {{ $notifBelum > 99 ? '99+' : $notifBelum }}
                </span>
            @endif
        </a>
        <div class="hidden text-right leading-tight sm:block">
            <div class="max-w-48 truncate text-sm font-semibold">{{ $namaUser }}</div>
            <div class="text-xs text-white/75">{{ $labelRole }}</div>
        </div>
        <div class="grid size-10 place-items-center rounded-full bg-white font-serif font-bold text-navy shadow">
            {{ mb_strtoupper(mb_substr($namaUser, 0, 1)) }}
        </div>
        </div>
    </div>
    </div>
</header>

<div id="ov" class="fixed inset-0 z-40 bg-navy/50 backdrop-blur-sm hidden lg:hidden"></div>

<div class="lg:flex">
    {{-- SIDEBAR --}}
    <aside id="sb" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-white/10 bg-navy
                          -translate-x-full transition-transform duration-200
                          lg:translate-x-0 lg:sticky lg:top-16 lg:z-20 lg:w-64 lg:h-[calc(100vh-4rem)] lg:self-start lg:shrink-0">
        <div class="flex h-16 items-center justify-between border-b border-white/10 px-4 lg:hidden">
            <div class="flex items-center gap-2 min-w-0">
                <x-logo size="w-9 h-9" />
                <span class="truncate font-serif font-semibold text-white">{{ config('sekolah.singkat') }}</span>
            </div>
            <button id="sbClose" type="button" aria-label="Tutup menu" class="rounded-lg p-2 text-white/75 hover:bg-white/10 cursor-pointer">
                <x-icon name="close" />
            </button>
        </div>

        <nav id="sbNav" class="flex-1 overflow-y-auto px-3 py-4">
            @foreach ($menu as [$judul, $items])
                <div class="mt-5 first:mt-0">
                    <div class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-white/55">{{ $judul }}</div>
                    @foreach ($items as [$label, $route])
                        @php $aktif = request()->routeIs($route.'*'); @endphp
                        <a href="{{ route($route) }}"
                           class="group flex items-center gap-3 rounded-xl border border-transparent px-2.5 py-2 text-sm transition
                                  {{ $aktif ? 'border-white/15 bg-white/15 font-semibold text-white' : 'font-medium text-white/80 hover:bg-white/10 hover:text-white' }}">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg transition
                                         {{ $aktif ? 'bg-white/15 text-white shadow-sm' : 'bg-white/10 text-sky group-hover:bg-white/15 group-hover:text-white' }}">
                                <x-icon :name="$ikon[$route] ?? 'dot'" size="w-5 h-5" />
                            </span>
                            <span class="truncate">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-3">
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-white/10 px-3 py-2.5 text-sm font-semibold text-white hover:bg-white/15 cursor-pointer transition">
                    <x-icon name="logout" size="w-4 h-4" /> Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- ISI --}}
    <main class="relative flex-1 min-w-0 overflow-hidden">
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -right-24 w-96 h-96 rounded-full bg-sky/15"></div>
        <div aria-hidden="true" class="pointer-events-none absolute bottom-24 -right-10 w-32 h-32 rounded-full bg-flame/10"></div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-8 py-8 pb-16">
            <div class="mb-7">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">@yield('heading')</h1>
                @hasSection('sub') <p class="text-mute mt-1.5 max-w-2xl">@yield('sub')</p> @endif
            </div>
            <x-pesan />
            @yield('content')
        </div>
    </main>
</div>

<script>
(function () {
    var sb = document.getElementById('sb'), ov = document.getElementById('ov');
    var nav = document.getElementById('sbNav');
    var sbToggle = document.getElementById('sbToggle');
    var kunciTertutup = 'sidebar-tertutup';

    function buka()  { sb.classList.remove('-translate-x-full'); ov.classList.remove('hidden'); }
    function tutup() { sb.classList.add('-translate-x-full'); ov.classList.add('hidden'); }
    document.getElementById('sbBtn').addEventListener('click', buka);
    document.getElementById('sbClose').addEventListener('click', tutup);
    ov.addEventListener('click', tutup);

    function aturTombolDesktop() {
        if (!sbToggle) return;
        var tertutup = sb.classList.contains('lg:hidden');
        sbToggle.setAttribute('aria-expanded', tertutup ? 'false' : 'true');
        sbToggle.setAttribute('aria-label', tertutup ? 'Buka menu' : 'Tutup menu');
    }

    try {
        if (localStorage.getItem(kunciTertutup) === '1') {
            sb.classList.add('lg:hidden');
        }
    } catch (e) {}
    aturTombolDesktop();

    if (sbToggle) {
        sbToggle.addEventListener('click', function () {
            sb.classList.toggle('lg:hidden');
            try {
                localStorage.setItem(kunciTertutup, sb.classList.contains('lg:hidden') ? '1' : '0');
            } catch (e) {}
            aturTombolDesktop();
        });
    }

    if (nav) {
        var posisiNav = 0;
        try {
            posisiNav = parseInt(sessionStorage.getItem('sidebar-scroll') || '0', 10) || 0;
        } catch (e) {}

        function pulihkanScroll() {
            if (posisiNav > 0) nav.scrollTop = posisiNav;
        }

        pulihkanScroll();
        window.addEventListener('load', pulihkanScroll);
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) pulihkanScroll();
        });

        nav.addEventListener('scroll', function () {
            try {
                sessionStorage.setItem('sidebar-scroll', String(nav.scrollTop));
            } catch (e) {}
        }, { passive: true });
    }
})();
</script>
</body>
</html>