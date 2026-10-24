@extends('layouts.app')
@section('title', 'Beranda')
@section('heading', 'Selamat datang, ' . $siswa['nama_lengkap'])
@section('sub', 'Pastikan data dirimu sudah benar. Kalau ada yang berubah, ajukan perubahan ke Tata Usaha.')
@section('content')
<div class="grid sm:grid-cols-3 gap-3 mb-6">
    <x-stat :angka="$siswa['kelas']" label="Kelas saat ini" />
    <x-stat :angka="$siswa['status']" label="Status siswa" />
    <x-stat :angka="count(array_filter($pengajuan, fn($p) => $p['status']==='menunggu'))" label="Pengajuan menunggu" />
</div>

<div class="card mb-4 border-l-4 !border-l-flame">
    <h2 class="text-lg font-bold">Periode update data tahunan sedang dibuka</h2>
    <p class="text-sm text-mute mt-1">Sampai 31 Okt 2026. Konfirmasi bahwa datamu tidak berubah, atau ajukan perubahan.</p>
    <div class="flex gap-2 mt-4 flex-wrap">
        <button class="btn">Data saya tidak berubah</button>
        <a href="{{ route('siswa.pengajuan') }}" class="btn btn-ghost">Ajukan perubahan</a>
    </div>
</div>

<div class="card">
    <h2 class="sec-title">Pengajuan terakhir</h2>
    @foreach ($pengajuan as $p)
        <div class="flex items-center justify-between gap-3 py-2.5 border-b border-line last:border-0 text-sm">
            <div><b>{{ $p['field'] }}</b> <span class="text-mute">· {{ $p['tgl'] }}</span></div>
            <x-badge :status="$p['status']" />
        </div>
    @endforeach
</div>
@endsection