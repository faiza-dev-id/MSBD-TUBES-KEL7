@extends('layouts.app')
@section('title', 'Siswa per Kelas')
@section('heading', 'Kelas ' . $row['nama'])
@section('sub', 'Wali kelas: ' . $row['wali'] . ' · ' . $row['jumlah'] . ' siswa')
@section('content')
<div class="card">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
        <input class="input !w-56" placeholder="Cari nama / NISN">
        <button class="btn btn-ghost">Unduh daftar</button>
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
<a href="{{ route('tu.kelas') }}" class="inline-block mt-4 text-sm text-flame-dark font-semibold">← Kembali ke daftar kelas</a>
@endsection