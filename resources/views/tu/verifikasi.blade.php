@extends('layouts.app')
@section('title', 'Verifikasi Berkas')
@section('heading', 'Verifikasi: ' . $row['nama'])
@section('sub', 'NISN ' . $row['nisn'] . ' · masuk ' . $row['tgl'])
@section('content')
<form class="grid lg:grid-cols-[1.3fr_1fr] gap-4 items-start" onsubmit="return false">
    <div class="space-y-4">
        @foreach ($checklist as $dokumen => $items)
            <div class="card">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="font-serif text-lg font-bold">{{ $dokumen }}</h2>
                    <a href="#" class="text-sm text-flame-dark font-semibold">Lihat berkas</a>
                </div>
                @foreach ($items as $item)
                    <label class="flex items-center gap-3 py-2 text-sm border-b border-line last:border-0 cursor-pointer">
                        <input type="checkbox" class="size-4 accent-flame"> {{ $item }}
                    </label>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="card lg:sticky lg:top-4">
        <h2 class="sec-title">Keputusan</h2>
        <div class="space-y-3">
            <div><label class="label">Alasan (jika dikembalikan / ditolak)</label>
                <select class="input"><option>Pilih alasan</option>@foreach ($alasan as $a)<option>{{ $a }}</option>@endforeach</select></div>
            <div><label class="label">Catatan untuk siswa</label><textarea class="input" rows="3"></textarea></div>
            <button class="btn w-full">Terima berkas</button>
            <button class="btn btn-ghost w-full">Kembalikan untuk diperbaiki</button>
            <button class="btn btn-ghost w-full !text-bad">Tolak</button>
            <hr class="border-line">
            <div><label class="label">Nilai ujian seleksi</label><input class="input" inputmode="decimal"></div>
            <button class="btn btn-flame w-full">Sahkan &amp; buat akun siswa</button>
            <p class="text-[11px] text-mute">Akun siswa dibuat otomatis memakai NISN.</p>
        </div>
    </div>
</form>
@endsection