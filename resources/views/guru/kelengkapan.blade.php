@extends('layouts.app')
@section('title', 'Kelengkapan Data')
@section('heading', 'Kelengkapan Data Kelas X IPA 1')
@section('sub', 'Pantau siapa yang sudah mengonfirmasi atau mengajukan perubahan selama periode update, lalu kirim pengingat.')
@section('content')
@php
    $hitung = collect($siswa)->countBy('status');
    $tanda = [
        'konfirmasi' => ['Sudah konfirmasi', 'bg-ok/15 text-ok'],
        'ubah'       => ['Mengajukan perubahan', 'bg-warn/15 text-warn'],
        'belum'      => ['Belum merespons', 'bg-bad/15 text-bad'],
    ];
@endphp
<div class="grid grid-cols-3 gap-3 mb-6">
    <x-stat :angka="$hitung['konfirmasi'] ?? 0" label="Sudah konfirmasi" />
    <x-stat :angka="$hitung['ubah'] ?? 0" label="Mengajukan perubahan" />
    <x-stat :angka="$hitung['belum'] ?? 0" label="Belum merespons" />
</div>
<div class="card">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
        <h2 class="font-serif text-lg font-bold">Status per siswa</h2>
        <button class="btn btn-flame">Kirim pengingat ke yang belum merespons</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[520px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Nama</th><th class="p-2">NISN</th><th class="p-2">Status</th><th class="p-2">Tanggal</th></tr></thead>
            <tbody>
            @foreach ($siswa as $s)
                <tr class="border-b border-line">
                    <td class="p-2.5 font-medium">{{ $s['nama'] }}</td><td class="p-2.5">{{ $s['nisn'] }}</td>
                    <td class="p-2.5"><span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $tanda[$s['status']][1] }}">{{ $tanda[$s['status']][0] }}</span></td>
                    <td class="p-2.5 text-mute">{{ $s['tgl'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection