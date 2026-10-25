@extends('layouts.app')
@section('title', 'Ringkasan')
@section('heading', 'Ringkasan Kepala Sekolah')
@section('sub', 'Gambaran singkat data siswa dan proses PPDB tahun ajaran ini.')
@section('content')
<x-hero judul="Tahun ajaran 2026/2027" sub="312 siswa aktif. Pantau proses PPDB, kelengkapan data, dan perubahan di sistem.">
    <a href="{{ route('admin.audit') }}" class="btn !bg-white !text-flame hover:!bg-tint">Lihat jejak audit</a>
    <a href="{{ route('admin.pengguna') }}" class="btn border border-white/40 !bg-white/15 !text-white hover:!bg-white/25">Kelola pengguna</a>
</x-hero>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
    <x-stat angka="312" label="Siswa aktif" ikon="users" warna="blue" />
    <x-stat angka="165" label="Total pendaftar" ikon="idcard" warna="grape" />
    <x-stat angka="142" label="Sudah disahkan" ikon="check" warna="leaf" />
    <x-stat angka="86%" label="Kelengkapan data" ikon="trend" warna="sun" />
    <x-stat angka="2 hari" label="Rata-rata waktu verifikasi" ikon="clock" warna="coral" />
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <div class="card">
        <h2 class="sec-title">Pendaftar per minggu</h2>
        <svg viewBox="0 0 400 150" class="w-full" role="img" aria-label="Grafik pendaftar per minggu">
            <defs>
                <linearGradient id="batang" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" style="stop-color: var(--color-sky)" />
                    <stop offset="1" style="stop-color: var(--color-flame)" />
                </linearGradient>
            </defs>
            @foreach ([40, 70, 90, 60, 30] as $i => $h)
                <rect x="{{ 20 + $i * 75 }}" y="{{ 125 - $h }}" width="45" height="{{ $h }}" fill="url(#batang)" rx="8"/>
                <text x="{{ 42 + $i * 75 }}" y="{{ 119 - $h }}" text-anchor="middle" font-size="11" font-weight="600" class="fill-navy">{{ $h }}</text>
                <text x="{{ 42 + $i * 75 }}" y="143" text-anchor="middle" font-size="11" class="fill-mute">M{{ $i + 1 }}</text>
            @endforeach
        </svg>
    </div>
    <div class="card">
        <h2 class="sec-title">Kelengkapan data per kelas</h2>
        @foreach ($kelas as $k)
            @php $pct = round(($k['sudah_konfirmasi'] + $k['mengajukan_perubahan']) / $k['total_siswa'] * 100); @endphp
            <x-progress :nilai="$pct" :label="$k['nama_kelas']" />
        @endforeach
    </div>
</div>
@endsection