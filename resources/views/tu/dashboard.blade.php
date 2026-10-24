@extends('layouts.app')
@section('title', 'Meja TU')
@section('heading', 'Meja Tata Usaha')
@section('sub', 'Periksa berkas PPDB dan pantau kelengkapan data. Nyaman dibuka dari HP.')
@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <x-stat angka="18" label="Menunggu diperiksa" />
    <x-stat angka="5" label="Perlu diperbaiki" />
    <x-stat angka="142" label="Disahkan" />
    <x-stat angka="7" label="Pengajuan ubah data" />
</div>

<div class="card mb-4">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
        <h2 class="font-serif text-lg font-bold">Antrean verifikasi PPDB</h2>
        <input class="input !w-56" placeholder="Cari nama / NISN">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Nama</th><th class="p-2">NISN</th><th class="p-2">Masuk</th><th class="p-2">Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($antrean as $r)
                <tr class="border-b border-line">
                    <td class="p-2.5 font-medium">{{ $r['nama'] }}</td><td class="p-2.5">{{ $r['nisn'] }}</td><td class="p-2.5">{{ $r['tgl'] }}</td>
                    <td class="p-2.5"><x-badge :status="$r['status']" /></td>
                    <td class="p-2.5 text-right"><a class="btn btn-ghost !py-1.5" href="{{ route('tu.verifikasi', $r['id']) }}">Periksa</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2 class="sec-title">Kelengkapan data per kelas (update tahunan)</h2>
    @foreach ($kelas as $k)
        @php $pct = round(($k['konfirmasi'] + $k['ubah']) / $k['total'] * 100); @endphp
        <div class="mb-3 text-sm">
            <div class="flex justify-between"><b>{{ $k['kelas'] }}</b><span class="text-mute">{{ $k['belum'] }} belum merespons · {{ $pct }}%</span></div>
            <div class="h-2 bg-line rounded-full mt-1 overflow-hidden"><div class="h-full bg-flame" style="width: {{ $pct }}%"></div></div>
        </div>
    @endforeach
    <button class="btn btn-ghost mt-2">Kirim pengingat ke siswa</button>
</div>
@endsection