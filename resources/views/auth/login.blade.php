@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<div class="relative min-h-screen grid place-items-center p-4 overflow-hidden">
    {{-- Hiasan latar --}}
    <div
        aria-hidden="true"
        class="absolute -top-20 -left-16 w-64 h-64 rounded-full bg-sky/25">
    </div>

    <div
        aria-hidden="true"
        class="absolute -bottom-28 -right-16 w-80 h-80 rounded-full bg-flame/20">
    </div>

    <div class="relative w-full max-w-4xl grid md:grid-cols-2 bg-white rounded-3xl shadow-2xl shadow-navy/20 overflow-hidden">

        {{-- FORM LOGIN --}}
        <div class="p-7 sm:p-10">
            <div class="inline-flex items-center gap-2 bg-flame text-white rounded-full pl-1.5 pr-4 py-1.5 text-xs font-semibold">
                <x-logo size="w-8 h-8" />
                <span>{{ config('sekolah.nama') }}</span>
            </div>

            <h1 class="mt-8 text-3xl font-bold">Masuk</h1>

            <p class="text-sm text-mute mt-1 mb-7">
                Masuk menggunakan akun sesuai peran kamu.
            </p>

            <form
                method="POST"
                action="{{ route('login.proses') }}"
                data-validasi
            >
                @csrf

                {{-- Pesan sukses --}}
                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-green-500/40 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Error validasi umum --}}
                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

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

                    {{-- Pilihan role --}}
                    <div>
                        <label class="label" for="role">
                            Masuk sebagai
                        </label>

                        <select
                            class="input"
                            id="role"
                            name="role"
                            required
                        >
                            @foreach ($roles as $key => $r)
                                <option
                                    value="{{ $key }}"
                                    @selected(old('role') == $key)
                                >
                                    {{ $r['label'] }}
                                </option>
                            @endforeach
                        </select>

                        @error('role')
                            <p class="text-xs text-bad mt-1">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Tombol login --}}
                    <button
                        type="submit"
                        class="btn btn-flame w-full !py-3"
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
        <div class="hidden md:flex relative flex-col justify-center items-center text-center p-10 text-white overflow-hidden bg-linear-to-b from-sky via-flame to-navy-soft">

            {{-- Hiasan panel --}}
            <div
                aria-hidden="true"
                class="absolute -top-10 left-8 w-28 h-28 rounded-b-full bg-white/25">
            </div>

            <div
                aria-hidden="true"
                class="absolute bottom-0 right-6 w-24 h-44 rounded-t-full bg-navy/80">
            </div>

            <div
                aria-hidden="true"
                class="absolute bottom-0 right-36 w-16 h-24 rounded-t-full bg-navy/60">
            </div>

            <h2 class="relative !text-white text-4xl font-bold leading-tight">
                Selamat<br>
                Datang
            </h2>

            <p class="relative mt-4 text-white/85 max-w-xs text-sm">
                Sistem {{ config('sekolah.sistem') }}
                {{ config('sekolah.nama') }},
                untuk siswa, guru, Tata Usaha, dan Kepala Sekolah.
            </p>
        </div>
    </div>
</div>
@endsection