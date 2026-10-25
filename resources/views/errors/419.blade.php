<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Kedaluwarsa · {{ config('sekolah.nama') }}</title>
    <link rel="icon" href="{{ asset(config('sekolah.logo')) }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen grid place-items-center px-4 py-10">
<main class="w-full max-w-md text-center">
    <div class="flex justify-center"><x-logo size="w-16 h-16" /></div>

    <p class="mt-4 font-serif font-bold text-flame leading-none text-[6.5rem] sm:text-[8rem]">419</p>
    <h1 class="mt-2 text-2xl font-bold">Halaman Kedaluwarsa</h1>
    <p class="mt-2 text-sm text-mute max-w-sm mx-auto">Sesi kamu sudah berakhir atau halaman terlalu lama terbuka. Data yang kamu kirim belum diproses.</p>

    <div class="card mt-7 text-left">
        <div class="flex justify-center text-sky"><x-icon name="clock" size="w-10 h-10" /></div>
        <h2 class="mt-3 text-center text-sm">Yang Bisa Anda Lakukan:</h2>
        <ul class="mt-4 space-y-2.5 text-sm text-ink/80">
            @foreach (['Buka kembali halaman sebelumnya dan muat ulang', 'Isi ulang formulir, lalu kirim lagi', 'Jika terus terjadi, hapus cookie situs ini atau hubungi Tata Usaha'] as $t)
                <li class="flex items-center gap-3">
                    <span class="grid place-items-center size-5 shrink-0 rounded-full bg-flame text-white">
                        <svg viewBox="0 0 24 24" class="size-3" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    {{ $t }}
                </li>
            @endforeach
        </ul>
    </div>

    <div class="mt-6 flex flex-wrap justify-center gap-2.5">
        <a href="{{ route('login') }}" class="btn btn-flame"><x-icon name="enter" size="w-4 h-4" /> Ke Halaman Login</a>
        <a href="{{ route('beranda') }}" class="btn btn-ghost"><x-icon name="home" size="w-4 h-4" /> Halaman Utama</a>
    </div>

    <p class="mt-7 text-xs text-mute">Butuh bantuan lebih lanjut? Hubungi Tata Usaha sekolah.</p>
</main>
</body>
</html>