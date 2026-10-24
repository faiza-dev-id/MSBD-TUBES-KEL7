@extends('layouts.guest')
@section('title', 'Masuk')
@section('content')
<div class="hidden lg:flex flex-col justify-between bg-navy text-paper p-12 border-r-8 border-flame">
    <x-logo size="w-16 h-16" />
    <div>
        <h1 class="!text-paper text-4xl font-bold leading-tight">Data siswa yang rapi,<br>pendaftaran yang jelas.</h1>
        <p class="mt-4 opacity-80 max-w-md">Sistem {{ config('sekolah.sistem') }} {{ config('sekolah.nama') }}, untuk siswa, guru, Tata Usaha, dan Kepala Sekolah.</p>
    </div>
    <p class="text-xs opacity-60">Proyek MSBD Kelompok 7 bersama mitra {{ config('sekolah.nama') }}</p>
</div>

<div class="flex items-center justify-center p-6">
    <form method="POST" action="{{ route('login.proses') }}" class="w-full max-w-sm">@csrf
        <x-logo size="w-14 h-14" class="lg:hidden mb-4" />
        <h2 class="text-2xl font-bold mb-1">Masuk</h2>
        <p class="text-sm text-mute mb-6">Mode rancangan: pilih peran untuk melihat tampilannya.</p>

        @error('username')
            <div class="mb-4 rounded-lg border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad">{{ $message }}</div>
        @enderror

        <div class="space-y-4">
            <x-field label="Username / NISN" name="username" placeholder="Contoh: 0012345678" />
            <x-field label="Kata sandi" name="password" type="password" />
            <div>
                <label class="label" for="role">Masuk sebagai</label>
                <select class="input" id="role" name="role" required>
                    @foreach ($roles as $key => $r) <option value="{{ $key }}">{{ $r['label'] }}</option> @endforeach
                </select>
                @error('role') <p class="text-xs text-bad mt-1">{{ $message }}</p> @enderror
            </div>
            <button class="btn btn-flame w-full">Masuk</button>
            <p class="text-[11px] text-mute">Uji tampilan error: ketik username <b>salah</b> (sandi keliru) atau <b>terkunci</b> (gagal beruntun).</p>
        </div>
    </form>
</div>
@endsection