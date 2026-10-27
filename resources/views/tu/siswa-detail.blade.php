@extends('layouts.app')
@section('title', 'Detail Siswa')
@section('heading', $siswa->nama_lengkap)
@section('sub', 'NISN ' . ($siswa->nisn ?? '-') . ' · NIS ' . ($siswa->nis ?? '-'))
@section('content')
<div class="grid lg:grid-cols-2 gap-4 items-start">
    <div class="card">
        <h2 class="font-serif text-lg font-bold mb-3">Data induk</h2>
        <dl class="grid grid-cols-[130px_1fr] gap-y-2.5 gap-x-3 text-sm">
            @foreach ([
                'NIS' => $siswa->nis,
                'NISN' => $siswa->nisn,
                'NIK' => $siswa->nik,
                'Nama lengkap' => $siswa->nama_lengkap,
                'Jenis kelamin' => $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                'Tempat lahir' => $siswa->tempat_lahir,
                'Tanggal lahir' => $siswa->tanggal_lahir,
                'Email' => $siswa->email,
                'No. telepon' => $siswa->no_telepon,
                'Alamat' => $alamat->alamat ?? '-',
            ] as $label => $value)
                <dt class="text-mute">{{ $label }}</dt><dd class="font-medium">{{ $value ?: '-' }}</dd>
            @endforeach
        </dl>
    </div>

    <div class="card">
        <h2 class="font-serif text-lg font-bold mb-3">Orang tua / wali</h2>
        @forelse ($orangTua as $ortu)
            <div class="py-2.5 border-b border-line last:border-0 text-sm">
                <b>{{ ucfirst($ortu->hubungan) }}: {{ $ortu->nama_lengkap }}</b>
                <div class="text-mute mt-1">Telepon: {{ $ortu->no_telepon ?: '-' }}</div>
                <div class="text-mute">Email: {{ $ortu->email ?: '-' }}</div>
                <div class="text-mute">Alamat: {{ $ortu->alamat ?: '-' }}</div>
            </div>
        @empty
            <p class="text-sm text-mute">Data orang tua/wali belum tersedia.</p>
        @endforelse
    </div>
</div>

<div class="card mt-4">
    <h2 class="font-serif text-lg font-bold mb-3">Dokumen siswa</h2>
    @forelse ($dokumen as $dok)
        <div class="py-2.5 border-b border-line last:border-0 text-sm flex items-center justify-between gap-3">
            <div>
                <b>{{ $dok->jenis_dokumen }}</b>
                <div class="text-xs text-mute">{{ $dok->nama_file_asli }} · Status: {{ $dok->status_verifikasi }}</div>
            </div>
            <span class="text-xs text-mute">{{ $dok->diunggah_pada }}</span>
        </div>
    @empty
        <p class="text-sm text-mute">Belum ada dokumen siswa.</p>
    @endforelse
</div>
<div class="mt-4"><a class="btn btn-ghost" href="{{ route('tu.siswa.index') }}">Kembali ke daftar siswa</a></div>
@endsection
