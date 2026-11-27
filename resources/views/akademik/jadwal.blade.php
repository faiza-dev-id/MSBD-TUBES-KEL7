@extends('layouts.app')
@section('title', 'Jadwal Pelajaran')
@section('heading', 'Penyusunan Jadwal Pelajaran')
@section('sub', 'Pilih semester dan kelas, lalu isi mata pelajaran dan guru untuk tiap jam pelajaran dari Senin hingga Sabtu.')
@section('content')
@php
    $daftarHari = $daftarHari ?? \App\Http\Controllers\PageController::daftarHariJadwal();
    $daftarSlot = $daftarSlot ?? \App\Http\Controllers\PageController::daftarSlotJadwal();
    $matriks = $matriks ?? [];

    $tanpaWali = collect($kelas)->filter(fn ($k) => str_starts_with((string) $k['wali'], '('))->pluck('nama');
    $bisaSimpan = $semesterTerpilih
        && (int) ($semesterTerpilih['id'] ?? 0) > 0
        && $kelasTerpilih
        && (int) ($kelasTerpilih['id'] ?? 0) > 0
        && count($mapel) > 0;
@endphp

<div class="space-y-3 mb-4">
    <div class="card bg-white !border-warn/40 text-sm text-warn">
        <b>Petunjuk:</b> Isi mata pelajaran pada jam yang ditentukan (1-8). Untuk jam istirahat tidak perlu diisi. Guru mata pelajaran bersifat opsional (jika dikosongkan otomatis memakai wali kelas).
    </div>
    @if (!$mapel)
        <div class="card bg-white !border-warn/40 text-sm text-warn">
            Belum ada mata pelajaran aktif. <a href="{{ route('akademik.konfigurasi', 'mapel') }}" class="font-semibold underline">Tambahkan mata pelajaran</a> terlebih dahulu agar jadwal bisa disimpan.
        </div>
    @endif
    @if (!$guru)
        <div class="card bg-white !border-warn/40 text-sm text-warn">
            Belum ada data guru pada sistem. Nama guru mata pelajaran tetap bisa diisi manual saat menyimpan jadwal.
        </div>
    @endif
</div>

<form method="GET" action="{{ route('akademik.jadwal') }}" class="card mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="label" for="pilih_semester">Semester</label>
        <select class="input !w-60" id="pilih_semester" name="semester" onchange="this.form.submit()">
            @foreach ($semester as $s)
                <option value="{{ $s['id'] }}" @selected((string) ($semesterTerpilih['id'] ?? '') === (string) $s['id'])>{{ $s['nama'] }} {{ $s['tahun'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="pilih_kelas">Kelas</label>
        <select class="input !w-44" id="pilih_kelas" name="kelas" onchange="this.form.submit()">
            @foreach ($kelas as $k)
                <option value="{{ $k['id'] }}" @selected((string) ($kelasTerpilih['id'] ?? '') === (string) $k['id'])>{{ $k['nama'] }}</option>
            @endforeach
        </select>
    </div>
</form>

@if (!$semesterTerpilih || !$kelasTerpilih)
    <div class="card text-sm text-mute">
        Data semester atau kelas belum tersedia. Lengkapi dulu di Konfigurasi Akademik sebelum menyusun jadwal.
    </div>
@else
    <form method="POST" action="{{ route('akademik.jadwal.simpan') }}" class="card">
        @csrf
        <input type="hidden" name="id_semester" value="{{ $semesterTerpilih['id'] }}">
        <input type="hidden" name="id_kelas" value="{{ $kelasTerpilih['id'] }}">

        <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
            <div>
                <div class="font-semibold text-base">{{ $kelasTerpilih['nama'] }}</div>
                <div class="text-xs text-mute">{{ $semesterTerpilih['nama'] }} {{ $semesterTerpilih['tahun'] }} · Wali kelas: {{ $kelasTerpilih['wali'] }}</div>
            </div>
        </div>

        @if (!$bisaSimpan)
            <div class="mb-4 rounded-lg border border-warn/40 bg-warn/10 px-4 py-3 text-sm text-warn">
                Jadwal belum bisa disimpan karena semester, kelas, atau mata pelajaran belum lengkap.
            </div>
        @endif

        <datalist id="list_guru">
            @foreach ($guru as $g)
                <option value="{{ $g['nama'] }}"></option>
            @endforeach
        </datalist>

        <div class="overflow-x-auto rounded-lg border border-line">
            <table class="w-full text-sm border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-tint/60 text-xs">
                        <th class="border border-line p-2 text-center w-28 min-w-[110px]">
                            <div class="flex flex-col justify-between h-12">
                                <span class="text-right text-[11px] font-bold text-ink">JAM</span>
                                <span class="text-left text-[11px] font-bold text-ink">HARI</span>
                            </div>
                        </th>
                        @foreach ($daftarSlot as $s => $slot)
                            <th class="border border-line p-2 text-center min-w-[125px] {{ in_array($s, [3, 6]) ? 'border-r-4 border-r-line' : '' }}">
                                <div class="font-bold text-ink text-xs">{{ $slot['label'] }}</div>
                                <div class="text-[11px] text-mute font-normal mt-0.5">{{ $slot['waktu'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftarHari as $h => $namaHari)
                        <tr class="hover:bg-tint/10 transition-colors">
                            <td class="border border-line bg-tint/40 p-2 text-center font-bold text-xs tracking-wider text-ink align-middle">
                                {{ $namaHari }}
                            </td>
                            @foreach ($daftarSlot as $s => $slot)
                                @php
                                    $cell = $matriks[$h][$s] ?? null;
                                    $idTerpilih = old("matriks.$h.$s.id_mata_pelajaran", $cell['id_mata_pelajaran'] ?? '');
                                    $guruTerpilih = old("matriks.$h.$s.nama_guru_mapel", $cell['nama_guru'] ?? '');
                                    $idJadwal = $cell['id_jadwal'] ?? '';
                                @endphp
                                <td class="border border-line p-1.5 align-top {{ in_array($s, [3, 6]) ? 'border-r-4 border-r-line' : '' }}">
                                    @if ($idJadwal)
                                        <input type="hidden" name="matriks[{{ $h }}][{{ $s }}][id_jadwal]" value="{{ $idJadwal }}">
                                    @endif
                                    <div class="space-y-1">
                                        <select name="matriks[{{ $h }}][{{ $s }}][id_mata_pelajaran]" class="input !text-xs !py-1 !px-1.5 w-full font-semibold" aria-label="{{ $namaHari }} Jam {{ $s }}">
                                            <option value="">— Kosong —</option>
                                            @foreach ($mapel as $m)
                                                <option value="{{ $m['id'] }}" @selected((string) $idTerpilih === (string) $m['id'])>{{ $m['nama'] }}</option>
                                            @endforeach
                                        </select>
                                        <input list="list_guru" name="matriks[{{ $h }}][{{ $s }}][nama_guru_mapel]" value="{{ $guruTerpilih }}" placeholder="Guru (opsional)" class="input !text-[11px] !py-0.5 !px-1.5 w-full text-mute" aria-label="Guru {{ $namaHari }} Jam {{ $s }}">
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3 flex-wrap">
            <span class="text-xs text-mute">
                * Kolom ganda menandakan jeda istirahat (09:00 - 09:20 dan 11:20 - 11:30).
            </span>
            <button class="btn btn-flame" @disabled(!$bisaSimpan)>Simpan jadwal</button>
        </div>
    </form>
@endif
@endsection
