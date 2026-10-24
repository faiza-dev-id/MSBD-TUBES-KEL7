@extends('layouts.app')
@section('title', 'Penempatan Siswa')
@section('heading', 'Penempatan Siswa ke Kelas')
@section('sub', 'Siswa baru yang sudah disahkan dan belum punya kelas.')
@section('content')
<form class="card max-w-2xl" onsubmit="return false">
    <h2 class="sec-title">Siswa belum berkelas</h2>
    @foreach ($belum as $s)
        <label class="flex items-center gap-3 py-2.5 text-sm border-b border-line last:border-0 cursor-pointer">
            <input type="checkbox" class="size-4 accent-[#d9822b]"> <b>{{ $s['nama'] }}</b> <span class="text-mute">{{ $s['nisn'] }}</span>
        </label>
    @endforeach
    <div class="mt-4 flex items-end gap-3 flex-wrap">
        <div class="flex-1 min-w-40"><label class="label">Tempatkan ke kelas</label><select class="input">@foreach ($kelas as $k)<option>{{ $k['nama'] }}</option>@endforeach</select></div>
        <button class="btn btn-flame">Tempatkan yang dipilih</button>
    </div>
</form>
@endsection