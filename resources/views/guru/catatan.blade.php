@extends('layouts.app')
@section('title', 'Catatan Wali Kelas')
@section('heading', 'Catatan Wali Kelas')
@section('sub', 'Catatan perkembangan siswa di kelas yang kamu walikan (X IPA 1).')
@section('content')
<div class="grid lg:grid-cols-[1fr_1.2fr] gap-4 items-start">
<form class="card space-y-4" method="POST" action="{{ route('guru.catatan.simpan') }}" data-validasi>
    @csrf
        <h2 class="sec-title">Tulis catatan</h2>
        <div><label class="label" for="id_siswa">Siswa</label><select class="input" id="id_siswa" name="id_siswa" data-wajib>@foreach ($siswa as $s)<option value="{{ $s['id'] ?? $s['nisn'] }}">{{ $s['nama'] }}</option>@endforeach</select></div>
        <div><label class="label" for="isi_catatan">Catatan</label><textarea class="input" id="isi_catatan" name="isi_catatan" data-wajib rows="4"></textarea></div>
        <button class="btn btn-flame">Simpan catatan</button>
    </form>
    <div class="card">
        <h2 class="sec-title">Riwayat catatan</h2>
        @foreach ($catatan as $c)
            <div class="py-3 border-b border-line last:border-0 text-sm">
                <div class="flex justify-between gap-2"><b>{{ $c['siswa'] }}</b><span class="text-xs text-mute">{{ $c['tgl'] }}</span></div>
                <p class="mt-1 text-mute">{{ $c['isi'] }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection