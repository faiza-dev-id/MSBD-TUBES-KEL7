@extends('layouts.app')
@section('title', 'Jadwal Pelajaran')
@section('heading', 'Penyusunan Jadwal Pelajaran')
@section('sub', 'Pilih kelas, lalu tentukan mata pelajaran tiap jam. Jadwal guru bentrok akan diberi peringatan.')
@section('content')
@php
    $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
    $jam  = ['07.00–07.45', '07.45–08.30', '08.30–09.15', '09.30–10.15', '10.15–11.00', '11.00–11.45'];
@endphp
<div class="card">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
        <div class="flex items-center gap-2"><label class="label !mb-0">Kelas</label>
            <select class="input !w-40">@foreach ($kelas as $k)<option>{{ $k['nama'] }}</option>@endforeach</select></div>
        <button class="btn btn-flame">Simpan jadwal</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[760px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2 w-28">Jam</th>@foreach ($hari as $h)<th class="p-2">{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach ($jam as $s => $j)
                <tr class="border-b border-line">
                    <td class="p-2 text-xs text-mute whitespace-nowrap">{{ $j }}</td>
                    @foreach ($hari as $h => $nama)
                        <td class="p-1.5">
                            <select class="input !py-1.5 !text-xs">
                                <option value="">—</option>
                                @foreach ($mapel as $m)<option {{ $m === $mapel[($h + $s * 2) % count($mapel)] ? 'selected' : '' }}>{{ $m }}</option>@endforeach
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