@extends('layouts.app')
@section('title', 'Ringkasan')
@section('heading', 'Ringkasan Kepala Sekolah')
@section('sub', 'Gambaran singkat data siswa dan proses PPDB tahun ajaran ini.')
@section('content')
@php
    $stat = $statistik ?? [];
    $kelas = $kelas ?? [];
    $mutasiMenunggu = $mutasiMenunggu ?? collect();
    $totalMutasiMenunggu = $totalMutasiMenunggu ?? 0;
@endphp
<x-hero judul="Tahun ajaran {{ $tahunAktif ?? '-' }}" sub="{{ $stat['siswa_aktif'] ?? 0 }} siswa aktif. Pantau proses PPDB, kelengkapan data, dan perubahan di sistem.">
    <a href="{{ route('admin.audit') }}" class="btn !bg-white !text-flame hover:!bg-tint">Lihat jejak audit</a>
    <a href="{{ route('admin.pengguna') }}" class="btn border border-white/40 !bg-white/15 !text-white hover:!bg-white/25">Kelola pengguna</a>
</x-hero>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
    <x-stat :angka="$stat['siswa_aktif'] ?? 0" label="Siswa aktif" ikon="users" warna="blue" motion />
    <x-stat :angka="$stat['total_pendaftar'] ?? 0" label="Total pendaftar" ikon="idcard" warna="grape" motion />
    <x-stat :angka="$stat['disahkan'] ?? 0" label="Sudah disahkan" ikon="check" warna="leaf" motion />
    <x-stat :angka="($stat['kelengkapan'] ?? null) !== null ? $stat['kelengkapan'] . '%' : '—'" label="Kelengkapan data" ikon="trend" warna="sun" motion />
    <x-stat :angka="($stat['rata_verifikasi'] ?? null) !== null ? $stat['rata_verifikasi'] . ' hari' : '—'" label="Rata-rata waktu verifikasi" ikon="clock" warna="coral" motion />
</div>

<div class="grid items-start gap-4 lg:grid-cols-2">
    <div class="card">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-line pb-3">
            <h2 class="flex items-center gap-2 font-serif text-base font-semibold text-navy">
                Persetujuan Mutasi Menunggu
                <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-sun/15 px-2 py-0.5 text-xs font-semibold text-sun-dark">{{ $totalMutasiMenunggu }}</span>
            </h2>
            <a href="{{ route('kepsek.mutasi') }}" class="text-xs font-semibold text-flame hover:underline">Lihat semua</a>
        </div>
        @forelse ($mutasiMenunggu as $m)
            <div class="flex items-start justify-between gap-3 border-b border-line py-3 first:pt-0 last:border-0 last:pb-0">
                <div class="min-w-0">
                    <b class="block truncate text-sm">{{ $m['nama'] }}</b>
                    <span class="text-xs text-mute">{{ $m['jenis'] }} · {{ $m['tgl'] }}</span>
                </div>
                <a href="{{ route('kepsek.mutasi') }}" class="btn btn-ghost shrink-0 !min-h-0 !px-4 !py-1.5 !text-xs">Tinjau</a>
            </div>
        @empty
            <p class="text-sm text-mute">Tidak ada pengajuan yang menunggu</p>
        @endforelse
    </div>
    <div class="card" id="kelas">
        <h2 class="sec-title">Kelengkapan data per kelas</h2>
        <x-filter-kelas :action="route('kepsek.dashboard') . '#kelas'" :filter="$filterKelas" />
        @forelse ($kelas as $k)
            @php $pct = $k['total_siswa'] > 0 ? round(($k['sudah_konfirmasi'] + $k['mengajukan_perubahan']) / $k['total_siswa'] * 100) : 0; @endphp
            <x-progress :nilai="$pct" :label="$k['nama_kelas']" />
        @empty
            <p class="text-sm text-mute">Belum ada kelas untuk filter ini.</p>
        @endforelse
        <x-pagination :paginator="$kelas" label="kelas" />
    </div>
</div>
@endsection
