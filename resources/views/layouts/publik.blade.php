<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('sekolah.nama') }}</title>
    <link rel="icon" href="{{ asset(config('sekolah.logo')) }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<header class="sticky top-0 z-30 h-16 flex items-center gap-3 px-4 lg:px-6 text-white bg-linear-to-r from-flame to-sky shadow-md">
    <x-logo size="w-10 h-10" />
    <div class="leading-tight min-w-0">
        <div class="font-serif font-semibold truncate">{{ config('sekolah.nama') }}</div>
        <div class="hidden sm:block text-xs text-white/80 truncate">{{ config('sekolah.sistem') }}</div>
    </div>
    <nav class="ml-auto flex items-center gap-1.5 text-sm">
        <a href="{{ route('ppdb') }}" class="hidden sm:block px-3 py-1.5 rounded-full hover:bg-white/15">Daftar PPDB</a>
        <a href="{{ route('ppdb.status') }}" class="hidden sm:block px-3 py-1.5 rounded-full hover:bg-white/15">Cek Status</a>
        <a href="{{ route('login') }}" class="px-4 py-1.5 rounded-full bg-white text-flame font-semibold">Masuk</a>
    </nav>
</header>

<main class="max-w-4xl mx-auto px-4 sm:px-8 py-8 pb-16">
    <div class="mb-7">
        <h1 class="text-2xl sm:text-3xl font-bold leading-tight">@yield('heading')</h1>
        @hasSection('sub') <p class="text-mute mt-1.5 max-w-2xl">@yield('sub')</p> @endif
    </div>
    @yield('content')
    <p class="text-xs text-mute text-center mt-12">Tampilan rancangan · data masih contoh</p>
</main>
</body>
</html>