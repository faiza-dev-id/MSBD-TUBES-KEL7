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
<x-public-header />

<main class="max-w-4xl mx-auto px-4 sm:px-8 py-8 pb-16">
    <div class="mb-7">
        <h1 class="text-2xl sm:text-3xl font-bold leading-tight">@yield('heading')</h1>
        @hasSection('sub') <p class="text-mute mt-1.5 max-w-2xl">@yield('sub')</p> @endif
    </div>
    @yield('content')
</main>
@stack('scripts')
</body>
</html>