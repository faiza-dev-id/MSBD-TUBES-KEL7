@extends('layouts.app')
@section('title', 'Penempatan Siswa')
@section('heading', 'Penempatan Siswa ke Kelas')
@section('sub', 'Siswa baru yang sudah disahkan dan belum punya kelas.')
@section('content')
<form class="card max-w-2xl" onsubmit="return false">
    <h2 class="sec-title">Siswa belum berkelas</h2>
    @foreach ($belum as $s)
        <label class="flex items-center gap-3 py-2.5 text-sm border-b border-line last:border-0 cursor-pointer">
            <input type="checkbox" class="size-4 accent-flame"> <b>{{ $s['nama'] }}</b> <span class="text-mute">{{ $s['nisn'] }}</span>
        </label>
    @endforeach
    <div class="mt-4 grid sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
        <div><label class="label">Tempatkan ke kelas</label><select class="input">@foreach ($kelas as $k)<option>{{ $k['nama'] }}</option>@endforeach</select></div>
        <div><label class="label" for="tgl_masuk">Tanggal masuk</label><input class="input" type="date" id="tgl_masuk" name="tgl_masuk" value="{{ now()->format('Y-m-d') }}"></div>
        <button class="btn btn-flame">Tempatkan yang dipilih</button>
    </div>
</form>
@endsection