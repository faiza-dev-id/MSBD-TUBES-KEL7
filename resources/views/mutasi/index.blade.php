@extends('layouts.app')
@section('title', $judul)
@section('heading', $judul)
@section('sub', $sub)
@section('content')
@if ($bisaAjukan)
    <form class="card max-w-2xl mb-4" method="POST" action="{{ route($simpanRoute) }}">
        @csrf
        <h2 class="sec-title">Ajukan mutasi siswa</h2>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label" for="id_siswa">Siswa</label>
                <select class="input" id="id_siswa" name="id_siswa" required>
                    <option value="">Pilih siswa</option>
                    @foreach ($siswa as $s)
                        <option value="{{ $s['id'] }}">{{ $s['nama'] }} - {{ $s['nisn'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="jenis">Jenis mutasi</label>
                <select class="input" id="jenis" name="jenis" required>
                    <option value="pindah_keluar">Pindah keluar</option>
                    <option value="pindah_masuk">Pindah masuk</option>
                    <option value="keluar">Keluar</option>
                </select>
            </div>
        </div>

        <div class="mt-3">
            <label class="label" for="id_sekolah_tujuan">Sekolah tujuan <span class="font-normal">(opsional)</span></label>
            <select class="input" id="id_sekolah_tujuan" name="id_sekolah_tujuan">
                <option value="">-</option>
                @foreach ($sekolah as $sk)
                    <option value="{{ $sk['id'] }}">{{ $sk['nama'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-3">
            <label class="label" for="alasan">Alasan mutasi</label>
            <textarea class="input" id="alasan" name="alasan" rows="3" maxlength="2000" required></textarea>
        </div>

        <button class="btn btn-flame mt-3">Kirim pengajuan mutasi</button>
    </form>
@endif

<div class="space-y-3">
    @forelse ($mutasi as $m)
        <div class="card">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <div class="text-xs text-mute">Mutasi #{{ $m['id'] }} - {{ $m['tgl'] }}</div>
                    <h2 class="font-serif text-lg font-bold">{{ $m['nama'] }}</h2>
                    <div class="text-xs text-mute">NISN {{ $m['nisn'] }}</div>
                </div>
                <x-badge :status="$m['status']" />
            </div>

            <dl class="grid sm:grid-cols-2 gap-x-4 gap-y-2 mt-3 text-sm">
                <div><dt class="text-xs text-mute">Jenis</dt><dd class="font-medium">{{ $m['jenis'] }}</dd></div>
                <div><dt class="text-xs text-mute">Sekolah tujuan</dt><dd class="font-medium">{{ $m['sekolah'] }}</dd></div>
                <div><dt class="text-xs text-mute">Diajukan oleh</dt><dd class="font-medium">{{ $m['pengaju'] }}</dd></div>
                <div><dt class="text-xs text-mute">Diputuskan oleh</dt><dd class="font-medium">{{ $m['penyetuju'] }}</dd></div>
                <div><dt class="text-xs text-mute">Tanggal keputusan</dt><dd class="font-medium">{{ $m['keputusan'] }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-mute">Alasan</dt><dd class="font-medium">{{ $m['alasan'] }}</dd></div>
            </dl>

            @if ($m['status'] === 'menunggu')
                <form method="POST" action="{{ route($putuskanRoute, $m['id']) }}" class="mt-4 flex gap-2 flex-wrap">
                    @csrf
                    <button class="btn" name="keputusan" value="setujui">Setujui mutasi</button>
                    <button class="btn btn-ghost !text-bad" name="keputusan" value="tolak">Tolak mutasi</button>
                </form>
            @endif
        </div>
    @empty
        <div class="card text-sm text-mute">
            Belum ada pengajuan mutasi siswa.
        </div>
    @endforelse
</div>
@endsection
