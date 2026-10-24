@extends('layouts.app')
@section('title', 'Absensi')
@section('heading', 'Absensi Kelas')
@section('sub', 'Buka sesi, catat kehadiran tiap siswa, lalu tutup sesi.')
@section('content')
<div class="grid lg:grid-cols-[1.3fr_1fr] gap-4 items-start">
    <div class="card">
        <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
            <h2 class="font-serif text-lg font-bold">X IPA 1 · Matematika · 24 Okt 2026</h2>
            <span class="text-xs font-semibold text-ok">Sesi dibuka</span>
        </div>
        <form onsubmit="return false">
            @foreach ($siswa as $i => $s)
                <div class="flex items-center justify-between gap-3 py-2.5 border-b border-line last:border-0 text-sm">
                    <span class="font-medium">{{ $s['nama'] }}</span>
                    <div class="flex gap-1">
                        @foreach (['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'] as $k => $t)
                            <label title="{{ $t }}"><input type="radio" name="abs{{ $i }}" value="{{ $k }}" class="peer hidden" {{ $k === 'H' ? 'checked' : '' }}>
                            <span class="inline-block w-9 text-center py-1.5 rounded-md border border-line text-xs font-semibold cursor-pointer peer-checked:bg-navy peer-checked:text-paper peer-checked:border-navy">{{ $k }}</span></label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div class="flex gap-2 mt-4 flex-wrap"><button class="btn btn-flame">Simpan kehadiran</button><button class="btn btn-ghost">Tutup sesi</button></div>
        </form>
    </div>

    <div class="space-y-4">
        <form class="card space-y-3" onsubmit="return false">
            <h2 class="sec-title">Buka sesi baru</h2>
            <div><label class="label">Jadwal</label><select class="input"><option>Senin · X IPA 1 · Matematika</option><option>Rabu · X IPA 1 · Matematika</option></select></div>
            <x-field label="Tanggal" name="tgl_sesi" type="date" />
            <x-field label="Topik pertemuan" name="topik" />
            <button class="btn w-full">Buka sesi</button>
        </form>
        <div class="card">
            <h2 class="sec-title">Sesi sebelumnya</h2>
            @foreach ($sesi as $s)
                <div class="py-2 border-b border-line last:border-0 text-sm"><b>{{ $s['tgl'] }}</b> · {{ $s['topik'] }}<span class="float-right text-xs text-mute">{{ $s['status'] }}</span></div>
            @endforeach
        </div>
    </div>
</div>

<div class="card mt-4">
    <h2 class="sec-title">Rekap kehadiran kelas</h2>
    <div class="overflow-x-auto"><table class="w-full text-sm min-w-[420px]">
        <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Nama</th><th class="p-2 text-center">H</th><th class="p-2 text-center">S</th><th class="p-2 text-center">I</th><th class="p-2 text-center">A</th></tr></thead>
        <tbody>@foreach ($rekap as $r)<tr class="border-b border-line"><td class="p-2.5 font-medium">{{ $r['nama'] }}</td><td class="p-2.5 text-center">{{ $r['h'] }}</td><td class="p-2.5 text-center">{{ $r['s'] }}</td><td class="p-2.5 text-center">{{ $r['i'] }}</td><td class="p-2.5 text-center {{ $r['a'] > 1 ? 'text-bad font-semibold' : '' }}">{{ $r['a'] }}</td></tr>@endforeach</tbody>
    </table></div>
</div>
@endsection