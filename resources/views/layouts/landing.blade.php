<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') · {{ config('sekolah.nama') }}</title>
    <link rel="icon" href="{{ asset(config('sekolah.logo', 'images/logo.png')) }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="theme-background min-h-screen bg-paper" style="--theme-foto: url('{{ asset('images/sekolah.jpeg') }}')">
<x-public-header />

<main>
    @yield('content')
</main>

<footer class="border-t border-line bg-white">
    <div class="mx-auto max-w-7xl px-4 py-6 text-center text-xs text-mute lg:px-8">
        &copy; {{ date('Y') }} {{ config('sekolah.nama') }}. Semua hak dilindungi.
    </div>
</footer>

@stack('scripts')
<x-toast-notification />
</body>
</html>