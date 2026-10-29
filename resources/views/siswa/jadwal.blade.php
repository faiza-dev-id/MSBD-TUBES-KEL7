@extends('layouts.app')
@section('title', 'Jadwal Pelajaran')
@section('heading', 'Jadwal Pelajaran')
@section('sub', 'Jadwal pelajaran harian beserta guru pengampu dan waktunya.')
@section('content')
@if (!$kelas)
    <div class="card text-sm text-mute">
        Kamu belum terdaftar di kelas pada semester aktif. Hubungi Tata Usaha agar kelasmu diatur terlebih dahulu.
    </div>
@else
    <div class="mb-4 text-sm text-mute">
        Kelas <b class="text-ink">{{ $kelas }}</b>
        @if ($semester)
            <span> - {{ $semester->nama_semester }}</span>
        @endif
    </div>

    @forelse ($jadwal as $hari => $items)
        <div class="card mb-3">
            <h2 class="sec-title">{{ $hari }}</h2>
            @foreach ($items as $j)
                <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0 text-sm">
                    <div class="min-w-0">
                        <b class="block">{{ $j['mapel'] }}</b>
                        <span class="text-xs text-mute">{{ $j['guru'] }}</span>
                    </div>
                    <div class="text-right shrink-0">
                        <b>{{ $j['mulai'] }} - {{ $j['selesai'] }}</b>
                        <div class="text-xs text-mute">Ruang {{ $j['ruang'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="card text-sm text-mute">
            Jadwal pelajaran belum disusun untuk kelasmu. Silakan cek kembali nanti.
        </div>
    @endforelse
@endif
@endsection
