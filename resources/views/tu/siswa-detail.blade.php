@extends('layouts.app')
@section('title', 'Detail Siswa')
@section('heading', $row['nama'])
@section('sub', 'NISN ' . $row['nisn'] . ' · ' . $row['kelas'] . ' · ' . $row['status'])
@section('content')
<div class="grid lg:grid-cols-2 gap-4 items-start">
    <div class="card">
        <div class="flex items-center justify-between mb-3"><h2 class="font-serif text-lg font-bold">Data induk</h2><button class="btn btn-ghost !py-1.5">Ubah data</button></div>
        <dl class="grid grid-cols-[130px_1fr] gap-y-2.5 gap-x-3 text-sm">
            @foreach (['NIK' => $s['nik'], 'No. KK' => $s['no_kk'], 'Jenis kelamin' => $s['jenis_kelamin'], 'Tempat, tgl lahir' => $s['tempat_lahir'].', '.$s['tanggal_lahir'], 'Agama' => $s['agama'], 'No. HP' => $s['no_hp'], 'Sekolah asal' => $s['sekolah_asal'], 'Alamat' => $s['alamat'], 'Ayah / Ibu' => $s['ayah'].' / '.$s['ibu']] as $k => $v)
                <dt class="text-mute">{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd>
            @endforeach
        </dl>
    </div>

    <div class="card">
        <div class="flex items-center justify-between mb-3"><h2 class="font-serif text-lg font-bold">Dokumen siswa</h2><button class="btn btn-ghost !py-1.5">+ Tambah</button></div>
        @foreach ($dokumen as $d)
            <div class="py-2.5 border-b border-line last:border-0 text-sm flex items-center justify-between gap-3">
                <div>
                    <b>{{ $d['jenis'] }}</b> <span class="text-mute">· {{ $d['format'] }}, {{ $d['ukuran'] }}</span>
                    <div class="text-xs text-mute">No. {{ $d['nomor'] }} · diunggah {{ $d['tgl'] }} · {{ $d['fisik'] ? 'fisik sudah diterima' : 'fisik belum diterima' }}</div>
                </div>
                <a href="#" class="text-flame-dark font-semibold">Lihat</a>
            </div>
        @endforeach
    </div>
</div>
@endsection