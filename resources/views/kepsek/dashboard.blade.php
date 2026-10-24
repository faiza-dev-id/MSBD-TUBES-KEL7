@extends('layouts.app')
@section('title', 'Ringkasan')
@section('heading', 'Ringkasan Kepala Sekolah')
@section('sub', 'Gambaran singkat data siswa dan proses PPDB tahun ajaran ini.')
@section('content')
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
    <x-stat angka="312" label="Siswa aktif" />
    <x-stat angka="165" label="Total pendaftar" />
    <x-stat angka="142" label="Sudah disahkan" />
    <x-stat angka="86%" label="Kelengkapan data" />
    <x-stat angka="2 hari" label="Rata-rata waktu verifikasi" />
</div>
<div class="grid lg:grid-cols-2 gap-4">
    <div class="card">
        <h2 class="sec-title">Pendaftar per minggu</h2>
        <svg viewBox="0 0 400 140" class="w-full" role="img" aria-label="Grafik pendaftar per minggu">
            @foreach ([40, 70, 90, 60, 30] as $i => $h)
                <rect x="{{ 20 + $i * 75 }}" y="{{ 120 - $h }}" width="45" height="{{ $h }}" class="fill-flame" rx="2"/>
                <text x="{{ 42 + $i * 75 }}" y="136" text-anchor="middle" font-size="11" class="fill-mute">M{{ $i + 1 }}</text>
            @endforeach
        </svg>
    </div>
    <div class="card">
        <h2 class="sec-title">Kelengkapan data per kelas</h2>
        @foreach ($kelas as $k)
            @php $pct = round(($k['konfirmasi'] + $k['ubah']) / $k['total'] * 100); @endphp
            <div class="mb-3 text-sm">
                <div class="flex justify-between"><b>{{ $k['kelas'] }}</b><span class="text-mute">{{ $pct }}%</span></div>
                <div class="h-2 bg-line rounded-full mt-1 overflow-hidden"><div class="h-full bg-navy" style="width: {{ $pct }}%"></div></div>
            </div>
        @endforeach
    </div>
</div>
@endsection