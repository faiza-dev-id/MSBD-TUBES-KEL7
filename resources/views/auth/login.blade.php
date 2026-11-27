@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
@php
    $fotoSekolah = is_file(public_path('images/sekolah.jpeg'))
        ? asset('images/sekolah.jpeg')
        : null;
@endphp
<div class="relative min-h-screen grid place-items-center px-4 py-10 sm:py-14 overflow-hidden">
    @if ($fotoSekolah)
        <img data-latar-foto src="{{ $fotoSekolah }}" alt="" class="absolute inset-0 size-full object-cover">
        <div aria-hidden="true" class="absolute inset-0 bg-navy/75"></div>
    @else
        <div aria-hidden="true" class="absolute inset-0 bg-linear-to-br from-navy via-navy-soft to-flame"></div>
    @endif

    {{-- Tombol kembali ke beranda --}}
    <a href="{{ route('home') }}" class="absolute top-4 left-4 z-20 inline-flex items-center gap-2 rounded-full bg-white/90 px-4 py-2 text-sm font-semibold text-navy shadow-lg shadow-navy/10 ring-1 ring-line transition hover:bg-white">
        <x-icon name="home" size="w-4 h-4" />
        <span>Kembali ke Beranda</span>
    </a>

    <div class="relative z-10 w-full max-w-3xl">
        <h1 class="mb-8 text-center font-serif text-3xl font-bold uppercase tracking-wide text-white sm:text-5xl">{{ config('sekolah.nama') }}</h1>

        <div class="grid overflow-hidden rounded-3xl bg-white shadow-2xl shadow-navy/20 md:grid-cols-2">

            {{-- FORM LOGIN --}}
            <div class="p-7 sm:p-9">
                <div class="inline-flex items-center gap-2 bg-flame text-white rounded-full pl-1.5 pr-4 py-1.5 text-xs font-semibold uppercase">
                    <x-logo size="w-8 h-8" />
                    <span>{{ config('sekolah.nama') }}</span>
                </div>

                <h2 class="mt-6 mb-4 text-3xl font-bold">Masuk</h2>

                <p class="text-sm text-mute mt-1 mb-6">
                    Masuk menggunakan username dan kata sandi akun.
                </p>

                <form
                    method="POST"
                    action="{{ route('login.proses') }}"
                    data-validasi
                >
                    @csrf

                    <div class="space-y-4">

                        {{-- Username --}}
                        <x-field
                            label="Username / NISN"
                            name="username"
                            placeholder="Masukkan username"
                            wajib="1"
                            autocomplete="username"
                        />

                        {{-- Password --}}
                        <x-field
                            label="Kata sandi"
                            name="password"
                            type="password"
                            wajib="1"
                            autocomplete="current-password"
                        />

                        {{-- Tombol login --}}
                        <button
                            type="submit"
                            class="btn btn-flame w-full !py-3.5 !text-base mt-2"
                        >
                            Masuk
                        </button>

                        {{-- Link PPDB --}}
                        <p class="text-sm text-center text-mute">
                            Calon siswa?
                            <a
                                href="{{ route('ppdb') }}"
                                class="font-semibold text-flame"
                            >
                                Daftar PPDB
                            </a>

                            ·

                            <a
                                href="{{ route('ppdb.status') }}"
                                class="font-semibold text-flame"
                            >
                                Cek status
                            </a>
                        </p>
                    </div>
                </form>
            </div>

            {{-- PANEL BIRU --}}
            <div class="relative hidden flex-col items-center justify-center overflow-hidden bg-linear-to-b from-navy-soft to-flame p-10 text-center md:flex">
                <div aria-hidden="true" class="absolute top-0 left-8 w-20 h-20 rounded-b-full bg-white/40"></div>
                <div aria-hidden="true" class="absolute bottom-0 right-0 w-20 h-44 rounded-t-full bg-navy"></div>
                <div aria-hidden="true" class="absolute bottom-0 right-24 w-14 h-24 rounded-t-full bg-navy/85"></div>

                <p class="relative font-serif text-4xl lg:text-5xl font-bold uppercase leading-tight text-white">
                    Selamat<br>Datang
                </p>

                <p class="relative mt-4 text-white/85 max-w-xs text-sm">
                    Sistem {{ config('sekolah.sistem') }}
                    {{ config('sekolah.nama') }},
                    untuk siswa, wali kelas, Tata Usaha, dan Kepala Sekolah.
                </p>
            </div>

        </div>
    </div>
</div>
@endsection