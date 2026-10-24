@extends('layouts.app')
@section('title', 'Profil Saya')
@section('heading', 'Profil Siswa')
@section('sub', 'Data yang tercatat di sekolah.')
@section('content')
<div class="card grid md:grid-cols-[180px_1fr] gap-6">
    <div class="aspect-[3/4] bg-paper border border-line rounded-lg grid place-items-center text-xs text-mute">Foto 3x4</div>
    <div>
        <h2 class="text-2xl font-bold mb-4">{{ $s['nama_lengkap'] }}</h2>
        <dl class="grid grid-cols-[130px_1fr] gap-y-2.5 gap-x-3 text-sm">
            @foreach ([
                'NISN' => $s['nisn'], 'NIK' => $s['nik'], 'No. KK' => $s['no_kk'], 'Jenis kelamin' => $s['jenis_kelamin'],
                'Tempat, tgl lahir' => $s['tempat_lahir'].', '.$s['tanggal_lahir'], 'Agama' => $s['agama'],
                'No. HP' => $s['no_hp'], 'Sekolah asal' => $s['sekolah_asal'], 'Alamat' => $s['alamat'],
                'Ayah / Ibu' => $s['ayah'].' / '.$s['ibu'],
            ] as $k => $v)
                <dt class="text-mute">{{ $k }}</dt><dd class="font-medium">{{ $v }}</dd>
            @endforeach
        </dl>
        <a href="{{ route('siswa.pengajuan') }}" class="btn mt-5">Ajukan perubahan data</a>
    </div>
</div>
@endsection