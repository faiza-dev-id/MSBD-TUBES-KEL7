@extends('layouts.app')
@section('title', 'Meja TU')
@section('heading', 'Meja Tata Usaha')
@section('sub', 'Periksa berkas PPDB dan pantau kelengkapan data.')
@section('content')
@php
    $pertama = collect($antrean)->firstWhere('status', 'menunggu');
@endphp

<x-hero :judul="$statistik['menunggu'] . ' berkas PPDB menunggu diperiksa'" sub="Mulai dari berkas PPDB yang paling lama masuk untuk diperiksa.">
    @if ($pertama)
        <a href="{{ route('tu.verifikasi', $pertama['id']) }}" class="btn !bg-white !text-flame hover:!bg-tint">Mulai periksa</a>
    @elseif ($statistik['menunggu'] > 0)
        <a href="{{ route('tu.antrean', ['status' => 'menunggu']) }}" class="btn !bg-white !text-flame hover:!bg-tint">Lihat antrean</a>
    @endif
</x-hero>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-stat :angka="$statistik['menunggu']" label="Menunggu diperiksa" ikon="clock" warna="sun" motion />
    <x-stat :angka="$statistik['perlu_perbaikan']" label="Perlu diperbaiki" ikon="edit" warna="coral" motion />
    <x-stat :angka="$statistik['disahkan']" label="Disahkan" ikon="check" warna="leaf" motion />
    <x-stat :angka="$statistik['pengajuan']" label="Pengajuan ubah data" ikon="file" warna="blue" motion />
</div>

<div class="mb-6 grid gap-3 sm:grid-cols-3">
    <x-tile :href="route('tu.siswa')" judul="Data Induk Siswa" ket="Cari dan lihat data siswa" ikon="users" warna="blue" motion />
    <x-tile :href="route('tu.periode')" judul="Periode Update" ket="Buka atau tutup update tahunan" ikon="calendar" warna="sun" motion />
</div>

<div class="card mb-4">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-serif text-lg font-bold">Antrean verifikasi PPDB</h2>
        <a class="btn btn-ghost !py-1.5" href="{{ route('tu.antrean') }}">Lihat semua &amp; cari</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead><tr class="border-b-2 border-line text-left text-xs text-mute"><th class="p-2">Nama</th><th class="p-2">NISN</th><th class="p-2">Masuk</th><th class="p-2">Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($antrean as $r)
                <tr class="border-b border-line transition hover:bg-tint/60">
                    <td class="p-2.5 font-medium">{{ $r['nama'] }}</td><td class="p-2.5">{{ $r['nisn'] }}</td><td class="p-2.5">{{ $r['tgl'] }}</td>
                    <td class="p-2.5"><x-badge :status="$r['status']" /></td>
                    <td class="p-2.5 text-right"><a class="btn btn-ghost !py-1.5" href="{{ route('tu.verifikasi', $r['id']) }}">Periksa</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-sm text-mute">Tidak ada antrean.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" id="kelas-tu">
    <h2 class="sec-title">Kelengkapan data per kelas (update tahunan)</h2>
    @if (!$periode)
        <p class="text-sm text-mute">Saat ini tidak ada periode akademik aktif.</p>
    @else
        <x-filter-kelas :action="route('tu.dashboard') . '#kelas-tu'" :filter="$filterKelas" />
        @forelse ($kelas as $k)
            @php $pct = $k['total_siswa'] > 0 ? round(($k['sudah_konfirmasi'] + $k['mengajukan_perubahan']) / $k['total_siswa'] * 100) : 0; @endphp
            <x-progress :nilai="$pct" :label="$k['nama_kelas']" :ket="$k['total_siswa'] . ' siswa, ' . $k['belum_respons'] . ' belum merespons'" />
        @empty
            <p class="text-sm text-mute">Belum ada kelas untuk filter ini.</p>
        @endforelse
        <x-pagination :paginator="$kelas" label="kelas" />
        <form method="POST" action="{{ route('tu.pengingat') }}" data-validasi data-konfirmasi="Kirim pengingat ke siswa aktif yang belum merespons dan belum diingatkan?">
            @csrf
            <button class="btn btn-ghost mt-3">Kirim pengingat ke siswa</button>
        </form>
    @endif
</div>
@endsection