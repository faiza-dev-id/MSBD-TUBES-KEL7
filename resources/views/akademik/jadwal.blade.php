@extends('layouts.app')
@section('title', 'Jadwal Pelajaran')
@section('heading', 'Penyusunan Jadwal Pelajaran')
@section('sub', 'Pilih semester dan kelas, lalu tentukan mata pelajaran dan guru tiap jam. Guru bentrok dan kelas tanpa wali diberi peringatan.')
@section('content')
@php
    $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
    $jam  = ['07.00–07.45', '07.45–08.30', '08.30–09.15', '09.30–10.15', '10.15–11.00', '11.00–11.45'];
    $tanpaWali = collect($kelas)->filter(fn ($k) => str_starts_with($k['wali'], '('))->pluck('nama');
@endphp

<div class="space-y-2 mb-4">
    <div class="rounded-lg border border-warn/40 bg-warn/10 px-4 py-3 text-sm text-warn">
        <b>Peringatan (contoh):</b> Bu Contoh Guru terjadwal di dua kelas pada Senin 07.00–07.45 (X IPA 1 dan XI IPA 1).
    </div>
    @if ($tanpaWali->isNotEmpty())
        <div class="rounded-lg border border-warn/40 bg-warn/10 px-4 py-3 text-sm text-warn">
            Kelas tanpa wali kelas: <b>{{ $tanpaWali->implode(', ') }}</b>. <a href="{{ route('tu.kelas') }}" class="font-semibold underline">Tetapkan sekarang</a>
        </div>
    @endif
</div>

<div class="card">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2"><label class="label !mb-0">Semester</label>
                <select class="input !w-52">@foreach ($semester as $s)<option>{{ $s[0] }} {{ $s[1] }}</option>@endforeach</select></div>
            <div class="flex items-center gap-2"><label class="label !mb-0">Kelas</label>
                <select class="input !w-36">@foreach ($kelas as $k)<option>{{ $k['nama'] }}</option>@endforeach</select></div>
        </div>
        <button class="btn btn-flame">Simpan jadwal</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2 w-28">Jam</th>@foreach ($hari as $h)<th class="p-2">{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach ($jam as $s => $j)
                <tr class="border-b border-line">
                    <td class="p-2 text-xs text-mute whitespace-nowrap">{{ $j }}</td>
                    @foreach ($hari as $h => $nama)
                        <td class="p-1.5 space-y-1">
                            <select class="input !py-1.5 !text-xs" aria-label="Mata pelajaran" data-native>
                                <option value="">— mapel —</option>
                                @foreach ($mapel as $m)<option {{ $m === $mapel[($h + $s * 2) % count($mapel)] ? 'selected' : '' }}>{{ $m }}</option>@endforeach
                            </select>
                            <select class="input !py-1.5 !text-xs" aria-label="Guru" data-native>
                                <option value="">— guru —</option>
                                @foreach ($guru as $g)<option {{ $g === $guru[($h + $s) % count($guru)] ? 'selected' : '' }}>{{ $g }}</option>@endforeach
                            </select>
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection