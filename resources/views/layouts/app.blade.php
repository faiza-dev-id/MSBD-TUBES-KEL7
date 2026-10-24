@php
    $role = session('role');
    $akun = ['Akun', [['Ganti Sandi', 'akun.sandi']]];
    $kelasMenu = ['Kelas', [['Daftar Kelas & Wali', 'tu.kelas'], ['Penempatan Siswa', 'tu.penempatan'], ['Kenaikan Kelas', 'tu.kenaikan']]];
    $akademikMenu = ['Akademik', [['Konfigurasi Akademik', 'akademik.konfigurasi'], ['Jadwal Pelajaran', 'akademik.jadwal']]];

    $menu = match ($role) {
        'siswa'  => [['Utama', [['Beranda', 'siswa.dashboard']]], ['Pendaftaran', [['Formulir PPDB', 'siswa.ppdb'], ['Status Pendaftaran', 'siswa.status']]], ['Data Saya', [['Profil Saya', 'siswa.profil'], ['Ubah Data', 'siswa.pengajuan']]], $akun],
        'guru'   => [['Mengajar', [['Jadwal Mengajar', 'guru.jadwal']]], ['Wali Kelas', [['Daftar Siswa Kelas', 'guru.kelas'], ['Catatan Wali Kelas', 'guru.catatan']]], $akun],
        'tu'     => [['Utama', [['Meja TU', 'tu.dashboard']]], ['Data Siswa', [['Data Induk Siswa', 'tu.siswa'], ['Persetujuan Data', 'tu.persetujuan'], ['Periode Update', 'tu.periode']]], $kelasMenu, $akademikMenu, $akun],
        'kepsek' => [['Utama', [['Ringkasan', 'kepsek.dashboard']]], $akun],
        'admin'  => [
            ['Pengguna & Keamanan', [['Daftar Pengguna', 'admin.pengguna'], ['Guru & Staf', 'admin.staf'], ['Peran & Akses', 'admin.peran'], ['Log Login', 'admin.log'], ['Jejak Audit', 'admin.audit'], ['Pengaturan Sistem', 'admin.pengaturan']]],
            ['Data Master', [['Data Referensi', 'admin.referensi'], ['Checklist & Alasan', 'admin.checklist'], ['Aturan Field', 'admin.aturan']]],
            $akademikMenu, $kelasMenu,
            ['Operasional', [['Meja TU', 'tu.dashboard'], ['Ringkasan Kepsek', 'kepsek.dashboard']]],
            $akun,
        ],
        default  => [],
    };
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
<body class="min-h-screen lg:flex">

<div id="ov" class="fixed inset-0 z-30 bg-black/40 hidden lg:hidden"></div>

<aside id="sb" class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-navy text-paper border-r-4 border-flame
                      -translate-x-full transition-transform lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:shrink-0">
    <div class="flex items-center gap-3 px-4 py-4 border-b border-paper/15">
        <x-logo size="w-11 h-11" />
        <div class="leading-tight">
            <div class="font-serif font-bold text-paper">{{ config('sekolah.singkat') }}</div>
            <div class="text-xs opacity-70">{{ config('sekolah.kota') }} · {{ config('sekolah.sistem') }}</div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        @foreach ($menu as [$judul, $items])
            <div class="mt-5 first:mt-0">
                <div class="px-3 mb-1 text-[11px] uppercase tracking-wider text-paper/50">{{ $judul }}</div>
                @foreach ($items as [$label, $route])
                    <a href="{{ route($route) }}"
                       class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs($route.'*') ? 'bg-flame text-white font-semibold' : 'text-paper/80 hover:bg-white/10' }}">{{ $label }}</a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="px-4 py-3 border-t border-paper/15 text-sm">
        <div class="font-semibold truncate">{{ session('nama') }}</div>
        <div class="text-xs opacity-70 mb-2">{{ \App\Support\DummyData::roles()[$role]['label'] ?? '' }}</div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="w-full px-3 py-1.5 rounded-md border border-paper/30 hover:bg-white/10 cursor-pointer">Keluar</button>
        </form>
    </div>
</aside>

<div class="flex-1 min-w-0">
    <div class="lg:hidden sticky top-0 z-20 flex items-center gap-3 bg-navy text-paper px-4 py-3 border-b-4 border-flame">
        <button id="sbBtn" type="button" aria-label="Buka menu" class="text-2xl leading-none cursor-pointer">☰</button>
        <x-logo size="w-8 h-8" />
        <div class="font-serif font-bold">{{ config('sekolah.nama') }}</div>
    </div>

    <main class="max-w-5xl mx-auto px-4 sm:px-8 py-6 pb-16">
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold leading-tight">@yield('heading')</h1>
            @hasSection('sub') <p class="text-mute mt-1 max-w-2xl">@yield('sub')</p> @endif
        </div>
        @yield('content')
        <p class="text-xs text-mute text-center mt-10">Tampilan rancangan · data masih contoh</p>
    </main>
</div>

<script>
(function () {
    var sb = document.getElementById('sb'), ov = document.getElementById('ov'), btn = document.getElementById('sbBtn');
    function tutup() { sb.classList.add('-translate-x-full'); ov.classList.add('hidden'); }
    btn.addEventListener('click', function () { sb.classList.remove('-translate-x-full'); ov.classList.remove('hidden'); });
    ov.addEventListener('click', tutup);
})();
</script>
</body>
</html>