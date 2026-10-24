@extends('layouts.app')
@section('title', 'Kenaikan Kelas')
@section('heading', 'Kenaikan Kelas Massal')
@section('sub', 'Pindahkan semua siswa satu kelas sekaligus. Hilangkan centang untuk siswa yang tinggal kelas.')
@section('content')
<form class="card max-w-2xl" onsubmit="return false">
    <div class="grid sm:grid-cols-2 gap-4 mb-4">
        <div><label class="label">Dari kelas</label><select class="input">@foreach ($kelas as $k)<option>{{ $k['nama'] }}</option>@endforeach</select></div>
        <div><label class="label">Ke kelas</label><select class="input">@foreach ($kelas as $k)<option>{{ $k['nama'] }}</option>@endforeach</select></div>
    </div>
    <h2 class="sec-title">Siswa yang naik kelas</h2>
    @foreach ($siswa as $s)
        <label class="flex items-center gap-3 py-2.5 text-sm border-b border-line last:border-0 cursor-pointer">
            <input type="checkbox" class="size-4 accent-flame" checked> <b>{{ $s['nama'] }}</b> <span class="text-mute">{{ $s['nisn'] }}</span>
        </label>
    @endforeach
    <p class="text-xs text-mute mt-3">Proses ini tercatat di jejak audit. Pastikan kelas tujuan sudah benar sebelum memproses.</p>
    <button class="btn btn-flame mt-3">Proses kenaikan kelas</button>
</form>
@endsection