@extends('layouts.guest')
@section('title', 'Masuk')
@section('content')
<div class="relative min-h-screen grid place-items-center px-4 py-10 sm:py-14 overflow-hidden">

    {{-- hiasan latar --}}
    <div aria-hidden="true" class="absolute top-0 left-[10%] w-24 sm:w-28 h-12 sm:h-14 rounded-b-full bg-flame"></div>
    <div aria-hidden="true" class="absolute bottom-0 right-0 w-28 sm:w-36 h-32 sm:h-40 rounded-t-full bg-flame"></div>

    <div class="relative z-10 w-full max-w-3xl">

        <h1 class="mb-8 text-center font-serif text-3xl sm:text-5xl font-bold uppercase tracking-wide text-flame">{{ config('sekolah.nama') }}</h1>

        <div class="grid md:grid-cols-2 bg-white rounded-3xl shadow-2xl shadow-navy/20 overflow-hidden">

            {{-- FORM --}}
            <div class="p-7 sm:p-9">
                <div class="inline-flex items-center gap-2 bg-flame text-white rounded-full pl-1.5 pr-4 py-1.5 text-xs font-semibold uppercase">
                    <x-logo size="w-8 h-8" />
                    <span>{{ config('sekolah.nama') }}</span>
                </div>

                <h2 class="mt-6 mb-6 text-3xl font-bold">Masuk</h2>

                <form method="POST" action="{{ route('login.proses') }}" data-validasi class="[&_.input]:border-sky/70 [&_.input]:py-3">@csrf
                    @error('username')
                        <div class="mb-4 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad">{{ $message }}</div>
                    @enderror

                    <div class="space-y-4">
                        <x-field label="Username / NISN" name="username" placeholder="Contoh: 0012345678" wajib="1" autocomplete="username" />
                        <x-field label="Kata sandi" name="password" type="password" wajib="1" autocomplete="current-password" />
                        <button class="btn btn-flame w-full !py-3.5 !text-base mt-2">Masuk</button>
                        <p class="text-sm text-center text-mute">Calon siswa? <a href="{{ route('ppdb') }}" class="font-semibold text-flame">Daftar PPDB</a> · <a href="{{ route('ppdb.status') }}" class="font-semibold text-flame">Cek status</a></p>
                    </div>
                </form>
            </div>

            {{-- PANEL BIRU --}}
            <div class="hidden md:flex relative flex-col justify-center items-center text-center p-10 overflow-hidden
                        bg-linear-to-b from-sky to-flame">
                <div aria-hidden="true" class="absolute top-0 left-8 w-20 h-20 rounded-b-full bg-white/40"></div>
                <div aria-hidden="true" class="absolute bottom-0 right-0 w-20 h-44 rounded-t-full bg-navy"></div>
                <div aria-hidden="true" class="absolute bottom-0 right-24 w-14 h-24 rounded-t-full bg-navy/85"></div>

                <p class="relative font-serif text-4xl lg:text-5xl font-bold uppercase leading-tight text-white">Selamat<br>Datang</p>
            </div>
        </div>
    </div>
</div>
@endsection