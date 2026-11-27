@extends('layouts.app')
@section('title', 'Absensi Kelas')
@section('heading', 'Absensi Kelas')
@section('sub', 'Catat kehadiran harian siswa di kelas yang kamu walikan.')
@section('content')
@if (!$kelasWali)
    <div class="card text-sm text-mute">
        Kamu belum ditetapkan sebagai wali kelas pada semester aktif. Hubungi Tata Usaha untuk penugasan wali kelas.
    </div>
@else
    <form method="GET" class="card mb-4 flex flex-wrap items-end gap-3" data-auto-filter>
        <div>
            <label class="label" for="kelas">Kelas</label>
            <input class="input" id="kelas" type="text" value="{{ $kelas['nama'] }}" readonly>
        </div>
        <div>
            <label class="label" for="tanggal">Tanggal</label>
            <input class="input" type="date" id="tanggal" name="tanggal" value="{{ $tanggal }}">
        </div>
        <a href="{{ route('guru.absensi') }}" class="btn btn-ghost">Reset</a>
    </form>

    @if (!$siswa)
        <div class="card text-sm text-mute">Belum ada siswa aktif di kelas ini.</div>
    @else
        <form class="card" method="POST" action="{{ route('guru.absensi.simpan') }}">
            @csrf
            <input type="hidden" name="id_kelas" value="{{ $kelas['id'] }}">
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
                <div class="min-w-0">
                    <h2 class="sec-title !mb-0">Presensi {{ \Illuminate\Support\Carbon::parse($tanggal)->locale('id')->translatedFormat('d M Y') }}</h2>
                </div>
                <span class="text-sm text-mute">{{ count($siswa) }} siswa</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="text-left text-xs text-mute border-b-2 border-line">
                            <th class="p-2 w-12">No</th>
                            <th class="p-2">Nama</th>
                            <th class="p-2">NISN</th>
                            <th class="p-2 w-36">Kehadiran</th>
                            <th class="p-2">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($siswa as $i => $s)
                            @php $kode = $presensi[$s['id']]['kode'] ?? 'H'; @endphp
                            <tr class="border-b border-line">
                                <td class="p-2.5 text-mute">{{ $i + 1 }}</td>
                                <td class="p-2.5 font-medium">{{ $s['nama'] }}</td>
                                <td class="p-2.5">{{ $s['nisn'] }}</td>
                                <td class="p-2.5">
                                    <select class="input !py-1.5" name="status[{{ $s['id'] }}]" aria-label="Kehadiran {{ $s['nama'] }}">
                                        <option value="H" @selected($kode === 'H')>Hadir</option>
                                        <option value="I" @selected($kode === 'I')>Izin</option>
                                        <option value="S" @selected($kode === 'S')>Sakit</option>
                                        <option value="A" @selected($kode === 'A')>Alpha</option>
                                    </select>
                                </td>
                                <td class="p-2.5">
                                    <input class="input !py-1.5" type="text" name="keterangan[{{ $s['id'] }}]" value="{{ $presensi[$s['id']]['keterangan'] ?? '' }}" maxlength="255" placeholder="Opsional">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button class="btn btn-flame mt-4">Simpan presensi</button>
        </form>
    @endif
@endif
@endsection
