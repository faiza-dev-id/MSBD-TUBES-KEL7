@extends('layouts.app')
@section('title', 'Konfirmasi Data')
@section('heading', 'Konfirmasi data tahunan')
@section('sub', 'Periksa data di bawah. Jika semuanya masih benar, konfirmasi. Jika ada yang berubah, ajukan perubahan.')
@section('content')
@php $dibuka = $periode['dibuka']; @endphp

@unless ($dibuka)
    <div class="mb-4 rounded-2xl border border-line bg-white px-4 py-3 text-sm text-mute" role="status">
        Periode update data sedang ditutup. Konfirmasi bisa dilakukan saat periode dibuka.
    </div>
@endunless

<div class="card mb-4">
    <h2 class="sec-title">Data kamu saat ini</h2>
    <dl class="grid grid-cols-[130px_1fr] gap-x-3 gap-y-2.5 text-sm">
        @foreach (['Nama' => $s['nama_lengkap'], 'NISN' => $s['nisn'], 'NIK' => $s['nik'], 'No. KK' => $s['no_kk'], 'Jenis kelamin' => $s['jenis_kelamin'], 'Tempat, tgl lahir' => $s['tempat_lahir'] . ', ' . $s['tanggal_lahir'], 'Agama' => $s['agama'], 'No. HP' => $s['no_hp'], 'Asal sekolah' => $s['sekolah_asal'], 'Alamat' => $s['alamat'], 'Ayah' => $s['ayah'], 'Ibu' => $s['ibu']] as $label => $nilai)
            <dt class="text-mute">{{ $label }}</dt><dd class="font-medium">{{ $nilai }}</dd>
        @endforeach
    </dl>
</div>

{{-- TODO backend: isi action dengan route konfirmasi data (POST) lalu hapus onsubmit. Field: id_periode, konfirmasi --}}
<form class="card space-y-4 {{ $dibuka ? '' : 'opacity-50' }}" method="POST" action="#" onsubmit="return false" data-validasi @if (! $dibuka) inert @endif>
    @csrf
    <input type="hidden" name="id_periode" value="{{ $periode['id'] ?? '' }}">
    <div data-grup data-minimal="1" data-pesan="Centang dulu untuk menyatakan datamu benar.">
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="konfirmasi" value="1" class="mt-0.5 size-4 accent-flame">
            <span>Saya menyatakan seluruh data di atas masih benar dan tidak ada yang berubah pada tahun ajaran <b>{{ $periode['tahun'] }}</b>.</span>
        </label>
    </div>
    <div class="flex flex-wrap gap-2">
        <button class="btn btn-flame">Konfirmasi data tidak berubah</button>
        <a href="{{ route('siswa.pengajuan') }}" class="btn btn-ghost">Ada yang berubah? Ajukan perubahan</a>
    </div>
</form>
@endsection