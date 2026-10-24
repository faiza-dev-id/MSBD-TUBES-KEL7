@extends('layouts.app')
@section('title', 'Daftar Siswa Kelas')
@section('heading', 'Daftar Siswa Kelas X IPA 1')
@section('sub', 'Kelas yang kamu walikan. Data induk lengkap hanya bisa diubah oleh Tata Usaha.')
@section('content')
<div class="card">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
        <input class="input !w-56" placeholder="Cari nama / NISN">
        <span class="text-sm text-mute">{{ count($siswa) }} siswa</span>
    </div>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2 w-12">No</th><th class="p-2">Nama</th><th class="p-2">NISN</th></tr></thead>
        <tbody>
        @foreach ($siswa as $i => $s)
            <tr class="border-b border-line"><td class="p-2.5 text-mute">{{ $i + 1 }}</td><td class="p-2.5 font-medium">{{ $s['nama'] }}</td><td class="p-2.5">{{ $s['nisn'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection