@extends('layouts.app')
@section('title', 'Daftar Kelas')
@section('heading', 'Kelas dan Wali Kelas')
@section('sub', 'Lihat kelas, tetapkan wali kelas, dan buka daftar siswa per kelas.')
@section('content')
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
    <form class="card space-y-4" onsubmit="return false">
        <h2 class="sec-title">Tetapkan wali kelas</h2>
        <div><label class="label">Kelas</label><select class="input">@foreach ($kelas as $k)<option>{{ $k['nama'] }}</option>@endforeach</select></div>
        <div><label class="label">Guru</label><select class="input"><option>Bu Contoh Guru</option><option>Pak Harahap</option></select></div>
        <x-field label="Berlaku mulai" name="tgl_mulai" type="date" />
        <button class="btn btn-flame w-full">Tetapkan</button>
    </form>
</div>
@endsection