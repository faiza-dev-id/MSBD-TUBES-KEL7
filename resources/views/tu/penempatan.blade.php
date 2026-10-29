@extends('layouts.app')
@section('title', 'Penempatan Siswa')
@section('heading', 'Penempatan Siswa ke Kelas')
@section('sub', 'Siswa baru yang sudah disahkan dan belum punya kelas.')
@section('content')
{{-- Field: id_siswa[], id_kelas, tgl_masuk --}}
<form class="card max-w-2xl" method="POST" action="{{ route('tu.penempatan.simpan') }}" data-validasi>
    @csrf
    <h2 class="sec-title">Siswa belum berkelas</h2>
    <div data-grup data-minimal="1" data-pesan="Pilih minimal satu siswa.">
    @foreach ($belum as $s)
        <label class="flex items-center gap-3 py-2.5 text-sm border-b border-line last:border-0 cursor-pointer">
            <input type="checkbox" name="id_siswa[]" value="{{ $s['id'] ?? $s['nisn'] }}" class="size-4 accent-flame"> <b>{{ $s['nama'] }}</b> <span class="text-mute">{{ $s['nisn'] }}</span>
        </label>
    @endforeach
    </div>
    <div class="mt-4 grid sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
        <div><label class="label" for="id_kelas">Tempatkan ke kelas</label><select class="input" id="id_kelas" name="id_kelas" data-wajib>@foreach ($kelas as $k)<option value="{{ $k['id'] }}">{{ $k['nama'] }}</option>@endforeach</select></div>
        <div><label class="label" for="tgl_masuk">Tanggal masuk</label><input class="input" data-wajib type="date" id="tgl_masuk" name="tgl_masuk" value="{{ now()->format('Y-m-d') }}"></div>
        <button class="btn btn-flame">Tempatkan yang dipilih</button>
    </div>
</form>
@endsection