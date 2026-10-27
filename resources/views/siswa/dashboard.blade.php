
@extends('layouts.app')

@section('title', 'Beranda')

@section('heading', 'Selamat datang, ' . ($siswa['nama_lengkap'] ?? 'Siswa'))

@section('sub', 'Pastikan data dirimu sudah benar. Kalau ada yang berubah, ajukan perubahan ke Tata Usaha.')

@section('content')

@php
    $periode = $periode ?? [
        'dibuka' => false,
        'tutup' => '-',
    ];

    $pengajuan = $pengajuan ?? [];

    $menunggu = collect($pengajuan)
        ->where('status', 'menunggu')
        ->count();
@endphp

@if ($periode['dibuka'])
    <x-hero
        judul="Periode update data tahunan sedang dibuka"
        :sub="'Sampai ' . ($periode['tutup'] ?? '-') . '. Konfirmasi bahwa datamu tidak berubah, atau ajukan perubahan.'"
    >
        <a href="{{ route('siswa.konfirmasi') }}"
           class="btn !bg-white !text-flame hover:!bg-tint">
            Data saya tidak berubah
        </a>

        <a href="{{ route('siswa.pengajuan') }}"
           class="btn border border-white/40 !bg-white/15 !text-white hover:!bg-white/25">
            Ajukan perubahan
        </a>
    </x-hero>
@else
    <x-hero
        warna="abu"
        judul="Periode update data sedang ditutup"
        sub="Konfirmasi dan pengajuan perubahan hanya bisa dilakukan saat periode dibuka. Tata Usaha akan mengumumkan jadwal berikutnya."
    />
@endif

<div class="mb-6 grid gap-3 sm:grid-cols-3">
    <x-stat
        :angka="$siswa['kelas'] ?? '-'"
        label="Kelas saat ini"
        ikon="home"
        warna="blue"
    />

    <x-stat
        :angka="$siswa['status'] ?? '-'"
        label="Status siswa"
        ikon="shield"
        warna="leaf"
    />

    <x-stat
        :angka="$menunggu"
        label="Pengajuan menunggu"
        ikon="clock"
        warna="sun"
    />
</div>

<div class="mb-6 grid gap-3 sm:grid-cols-2">
    <x-tile
        :href="route('siswa.profil')"
        judul="Profil Saya"
        ket="Lihat data yang tercatat di sekolah"
        ikon="user"
        warna="blue"
    />

    <x-tile
        :href="route('siswa.pengajuan')"
        judul="Ubah Data"
        ket="Ajukan perubahan beserta bukti"
        ikon="edit"
        warna="grape"
    />
</div>

<div class="card">
    <h2 class="sec-title">Pengajuan terakhir</h2>

    @forelse ($pengajuan as $p)
        <div class="flex items-center justify-between gap-3 border-b border-line py-3 text-sm last:border-0">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-tint text-flame">
                    <x-icon name="file" size="w-4 h-4" />
                </span>

                <div class="min-w-0">
                    <b class="block truncate">
                        {{ $p['field'] ?? 'Pengajuan perubahan' }}
                    </b>

                    <span class="text-xs text-mute">
                        {{ $p['tgl'] ?? '-' }}
                    </span>
                </div>
            </div>

            <x-badge :status="$p['status'] ?? 'menunggu'" />
        </div>
    @empty
        <div class="py-8 text-center">
            <p class="font-medium">Belum ada pengajuan</p>
            <p class="mt-1 text-sm text-mute">
                Riwayat pengajuan perubahan datamu akan muncul di sini.
            </p>
        </div>
    @endforelse
</div>

@endsection
