@extends('layouts.app')
@section('title', 'Daftar Kelas')
@section('heading', 'Kelas dan Wali Kelas')
@section('sub', 'Lihat kelas, tetapkan wali kelas, dan buka daftar siswa per kelas.')
@section('content')
@php
    // Peringatan: kelas tanpa wali, dan guru yang tercatat menjadi wali di beberapa kelas.
    $tanpaWali = collect($kelas)->filter(fn ($k) => str_starts_with($k['wali'], '('))->pluck('nama');
    $waliGanda = collect($kelas)->reject(fn ($k) => str_starts_with($k['wali'], '('))->groupBy('wali')->filter(fn ($g) => $g->count() > 1);
@endphp
@if ($tanpaWali->isNotEmpty())
    <div class="mb-4 rounded-2xl border border-sun/40 bg-sun/10 px-4 py-3 text-sm text-sun-dark" role="status">
        Kelas yang belum punya wali: <b>{{ $tanpaWali->join(', ') }}</b>.
    </div>
@endif
@foreach ($waliGanda as $nama => $grup)
    <div class="mb-4 rounded-2xl border border-sun/40 bg-sun/10 px-4 py-3 text-sm text-sun-dark" role="status">
        Periksa lagi: <b>{{ $nama }}</b> tercatat menjadi wali di {{ $grup->pluck('nama')->join(' dan ') }}.
    </div>
@endforeach
<div class="grid lg:grid-cols-[1.4fr_1fr] gap-4 items-start">
    <div class="card">
        <h2 class="sec-title">Daftar kelas</h2>
        @foreach ($kelas as $k)
            <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0 text-sm">
                <div><b>{{ $k['nama'] }}</b> <span class="text-mute">· {{ $k['jumlah'] }} siswa</span>
                    <div class="text-xs {{ str_starts_with($k['wali'], '(') ? 'text-bad' : 'text-mute' }}">Wali: {{ $k['wali'] }}</div></div>
                <a href="{{ route('tu.kelas.detail', $k['id']) }}" class="btn btn-ghost !py-1.5">Daftar siswa</a>
            </div>
        @endforeach
    </div>
    {{-- TODO backend: isi action dengan route penetapan wali kelas (POST) lalu hapus onsubmit. Field: id_kelas, id_guru, tgl_mulai, tgl_selesai --}}
    <form class="card space-y-4" method="POST" action="#" onsubmit="return false" data-validasi>
        @csrf
        <h2 class="sec-title">Tetapkan wali kelas</h2>
        <div><label class="label" for="id_kelas">Kelas</label><select class="input" id="id_kelas" name="id_kelas" data-wajib>@foreach ($kelas as $k)<option value="{{ $k['id'] }}">{{ $k['nama'] }}</option>@endforeach</select></div>
        {{-- value guru masih nama; ganti ke id_guru saat data dari database --}}
        <div><label class="label" for="id_guru">Guru</label><select class="input" id="id_guru" name="id_guru" data-wajib>@foreach (\App\Support\DummyData::daftarGuru() as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
        <x-field label="Berlaku mulai" name="tgl_mulai" type="date" wajib="1" />
        <x-field label="Berlaku sampai (opsional)" name="tgl_selesai" type="date" setelah="#tgl_mulai" hint="Kosongkan jika masih berlaku" />
        <button class="btn btn-flame w-full">Tetapkan</button>
    </form>
</div>
@endsection