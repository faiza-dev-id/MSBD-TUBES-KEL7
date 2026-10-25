@extends('layouts.app')
@section('title', 'Meja TU')
@section('heading', 'Meja Tata Usaha')
@section('sub', 'Periksa berkas PPDB dan pantau kelengkapan data. Nyaman dibuka dari HP.')
@section('content')
@php
    $menunggu = collect($antrean)->where('status', 'menunggu');
    $pertama  = $menunggu->first();
@endphp

<x-hero :judul="$menunggu->count() . ' berkas PPDB menunggu diperiksa'" sub="Mulai dari yang paling lama masuk, lalu lanjut ke persetujuan perubahan data siswa.">
    @if ($pertama)
        <a href="{{ route('tu.verifikasi', $pertama['id']) }}" class="btn !bg-white !text-flame hover:!bg-tint">Mulai periksa</a>
    @endif
    <a href="{{ route('tu.persetujuan') }}" class="btn border border-white/40 !bg-white/15 !text-white hover:!bg-white/25">Persetujuan data</a>
</x-hero>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-stat angka="18" label="Menunggu diperiksa" ikon="clock" warna="sun" />
    <x-stat angka="5" label="Perlu diperbaiki" ikon="edit" warna="coral" />
    <x-stat angka="142" label="Disahkan" ikon="check" warna="leaf" />
    <x-stat angka="7" label="Pengajuan ubah data" ikon="file" warna="blue" />
</div>

<div class="mb-6 grid gap-3 sm:grid-cols-3">
    <x-tile :href="route('tu.siswa')" judul="Data Induk Siswa" ket="Cari dan lihat data siswa" ikon="users" warna="blue" />
    <x-tile :href="route('tu.persetujuan')" judul="Persetujuan Data" ket="Pengajuan perubahan dari siswa" ikon="clipboard" warna="grape" />
    <x-tile :href="route('tu.periode')" judul="Periode Update" ket="Buka atau tutup update tahunan" ikon="calendar" warna="sun" />
</div>

<div class="card mb-4">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-serif text-lg font-bold">Antrean verifikasi PPDB</h2>
        <input class="input !w-56" placeholder="Cari nama / NISN">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead><tr class="border-b-2 border-line text-left text-xs text-mute"><th class="p-2">Nama</th><th class="p-2">NISN</th><th class="p-2">Masuk</th><th class="p-2">Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($antrean as $r)
                <tr class="border-b border-line transition hover:bg-tint/60">
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
        @php $pct = round(($k['sudah_konfirmasi'] + $k['mengajukan_perubahan']) / $k['total_siswa'] * 100); @endphp
        <x-progress :nilai="$pct" :label="$k['nama_kelas']" :ket="$k['belum_respons'] . ' belum merespons'" />
    @endforeach
    <button class="btn btn-ghost mt-3">Kirim pengingat ke siswa</button>
</div>
@endsection