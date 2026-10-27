@extends('layouts.app')
@section('title', 'Kenaikan Kelas')
@section('heading', 'Kenaikan Kelas Massal')
@section('sub', 'Pindahkan semua siswa satu kelas sekaligus. Hilangkan centang untuk siswa yang tinggal kelas.')
@section('content')
{{-- TODO backend: isi action dengan route kenaikan kelas massal (POST) lalu hapus onsubmit. Field: id_kelas_asal, id_kelas_tujuan, id_siswa[] --}}
<form class="card max-w-2xl" method="POST" action="#" onsubmit="return false" data-validasi data-konfirmasi="Proses kenaikan kelas untuk semua siswa yang dicentang? Tindakan ini tercatat di jejak audit.">
    @csrf
    <div class="grid sm:grid-cols-2 gap-4 mb-4">
        <div><label class="label" for="id_kelas_asal">Dari kelas</label><select class="input" id="id_kelas_asal" name="id_kelas_asal" data-wajib>@foreach ($kelas as $k)<option value="{{ $k['id'] }}">{{ $k['nama'] }}</option>@endforeach</select></div>
        <div><label class="label" for="id_kelas_tujuan">Ke kelas</label><select class="input" id="id_kelas_tujuan" name="id_kelas_tujuan" data-wajib data-beda="#id_kelas_asal" data-pesan="Kelas tujuan tidak boleh sama dengan kelas asal.">@foreach ($kelas as $k)<option value="{{ $k['id'] }}">{{ $k['nama'] }}</option>@endforeach</select></div>
    </div>
    <h2 class="sec-title">Siswa yang naik kelas</h2>
    <div data-grup data-minimal="1" data-pesan="Pilih minimal satu siswa yang naik kelas.">
    @foreach ($siswa as $s)
        <label class="flex items-center gap-3 py-2.5 text-sm border-b border-line last:border-0 cursor-pointer">
            <input type="checkbox" name="id_siswa[]" value="{{ $s['id'] ?? $s['nisn'] }}" class="size-4 accent-flame" checked> <b>{{ $s['nama'] }}</b> <span class="text-mute">{{ $s['nisn'] }}</span>
        </label>
    @endforeach
    </div>
    <p class="text-xs text-mute mt-3">Proses ini tercatat di jejak audit. Pastikan kelas tujuan sudah benar sebelum memproses.</p>
    <button class="btn btn-flame mt-3">Proses kenaikan kelas</button>
</form>
@endsection