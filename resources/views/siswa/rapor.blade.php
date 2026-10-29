@extends('layouts.app')
@section('title', 'Rapor Saya')
@section('heading', 'Rapor Saya')
@section('sub', 'Nilai rapor yang disusun oleh wali kelas.')
@section('content')
@if (!$rapor)
    <div class="card text-sm text-mute">
        Rapor belum tersedia. Wali kelas akan mengisi nilaimu pada semester aktif.
        @if ($semester)
            <span> Semester: <b class="text-ink">{{ $semester->nama_semester }}</b>.</span>
        @endif
    </div>
@else
    <div class="mb-4 grid gap-3 sm:grid-cols-4">
        <div class="card !p-4">
            <div class="text-xs text-mute">Rata-rata</div>
            <div class="mt-1 text-2xl font-bold">{{ rtrim(rtrim(number_format((float) $rapor->rata_rata, 2, '.', ''), '0'), '.') ?: '-' }}</div>
        </div>
        <div class="card !p-4">
            <div class="text-xs text-mute">Sakit</div>
            <div class="mt-1 text-2xl font-bold">{{ $rapor->jumlah_sakit }}</div>
        </div>
        <div class="card !p-4">
            <div class="text-xs text-mute">Izin</div>
            <div class="mt-1 text-2xl font-bold">{{ $rapor->jumlah_izin }}</div>
        </div>
        <div class="card !p-4">
            <div class="text-xs text-mute">Alpha</div>
            <div class="mt-1 text-2xl font-bold">{{ $rapor->jumlah_alpa }}</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h2 class="sec-title !mb-1">{{ $kelas ?? '-' }}</h2>
                <p class="text-xs text-mute">{{ $semester->nama_semester ?? '-' }} - Status: {{ ucfirst($rapor->status_rapor) }}</p>
            </div>
        </div>

        <table class="w-full text-sm mt-3">
            <thead>
                <tr class="text-left text-xs text-mute border-b-2 border-line">
                    <th class="p-2 w-12">No</th>
                    <th class="p-2">Mata pelajaran</th>
                    <th class="p-2 w-24">Nilai</th>
                    <th class="p-2 w-24">Predikat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($detail as $i => $d)
                    <tr class="border-b border-line">
                        <td class="p-2.5 text-mute">{{ $i + 1 }}</td>
                        <td class="p-2.5 font-medium">{{ $d['mapel'] }}</td>
                        <td class="p-2.5">{{ $d['nilai'] }}</td>
                        <td class="p-2.5">{{ $d['predikat'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-mute">Belum ada nilai mata pelajaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2 class="sec-title">Catatan wali kelas</h2>
        <p class="text-sm text-mute">{{ $rapor->catatan_wali ?: 'Belum ada catatan.' }}</p>
    </div>
@endif
@endsection
