@extends('layouts.app')
@section('title', 'Kehadiran Saya')
@section('heading', 'Kehadiran Saya')
@section('sub', 'Rekap dan riwayat kehadiranmu pada setiap sesi presensi kelas.')
@section('content')
@php
    $warna = [
        'hadir' => 'bg-leaf/12 text-leaf',
        'izin'  => 'bg-sun/15 text-sun-dark',
        'sakit' => 'bg-sky/15 text-navy',
        'alpha' => 'bg-bad/12 text-bad',
    ];
@endphp

<div class="mb-5 grid gap-3 sm:grid-cols-4">
    @foreach ([
        'hadir' => ['Hadir', 'leaf'],
        'izin'  => ['Izin', 'sun'],
        'sakit' => ['Sakit', 'blue'],
        'alpha' => ['Alpha', 'flame'],
    ] as $kode => $label)
        <div class="card !p-4">
            <div class="text-xs text-mute">{{ $label[0] }}</div>
            <div class="mt-1 text-2xl font-bold">{{ $rekap[$kode] ?? 0 }}</div>
        </div>
    @endforeach
</div>

<div class="card">
    <h2 class="sec-title">Riwayat kehadiran</h2>
    @forelse ($riwayat as $r)
        <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0 text-sm">
            <div class="min-w-0">
                <b class="block">{{ $r['tanggal'] }}</b>
                <span class="text-xs text-mute">{{ $r['mapel'] }} - {{ $r['guru'] }}</span>
                @if ($r['keterangan'] !== '-')
                    <div class="text-xs text-mute">Keterangan: {{ $r['keterangan'] }}</div>
                @endif
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $warna[$r['kode']] ?? 'bg-line text-mute' }}">
                {{ $r['status'] }}
            </span>
        </div>
    @empty
        <div class="py-8 text-center text-sm text-mute">
            Belum ada data kehadiran yang tercatat.
        </div>
    @endforelse
</div>
@endsection
