@extends('layouts.publik')
@section('title', 'Cek Status Pendaftaran')
@section('heading', 'Cek Status Pendaftaran')
@section('sub', 'Masukkan NISN dan kode pendaftaran yang kamu terima saat mengirim formulir. Kabar status juga dikirim lewat WhatsApp.')
@section('content')
<form class="card mb-4 grid sm:grid-cols-[1fr_1fr_auto] gap-4 items-end" method="GET" action="{{ route('ppdb.status') }}" data-validasi>
    <x-field label="NISN" name="cek_nisn" placeholder="10 digit" digit="10" />
    <x-field label="Kode pendaftaran" name="cek_kode" placeholder="Contoh: PPDB-20261025-AB12CD" wajib="1"
        pola="^PPDB-[0-9]{8}-[A-Z0-9]{6}$" pesan="Kode pendaftaran tidak sesuai format. Contoh: PPDB-20261025-AB12CD." />
    <button class="btn btn-flame">Cek status</button>
</form>

@if ($hasil)
    <p class="text-xs text-mute mb-3">Hasil pencarian untuk <b>{{ $hasil->nama_calon_siswa }}</b> · {{ $hasil->nomor_pendaftaran }}.</p>
@elseif (request()->filled('cek_nisn') || request()->filled('cek_kode'))
    <p class="text-xs text-bad mb-3">Pendaftaran tidak ditemukan. Periksa kembali NISN dan kode pendaftaran.</p>
@else
    <p class="text-xs text-mute mb-3">Masukkan NISN atau kode pendaftaran untuk melihat status.</p>
@endif

@if ($hasil)
    @php
        $labelStatus = [
            'draf' => 'Draf',
            'menunggu' => 'Menunggu verifikasi',
            'perlu_perbaikan' => 'Perlu perbaikan',
            'diverifikasi' => 'Terverifikasi',
            'diterima' => 'Diterima',
            'ditolak' => 'Perlu perbaikan',
        ][$hasil->status_pendaftaran] ?? $hasil->status_pendaftaran;
    @endphp
    <div class="card mb-4">
        <div class="grid sm:grid-cols-4 gap-3 text-sm">
            <div><span class="text-xs text-mute">Nomor pendaftaran</span><div class="font-semibold">{{ $hasil->nomor_pendaftaran }}</div></div>
            <div><span class="text-xs text-mute">NISN</span><div class="font-semibold">{{ $hasil->nisn }}</div></div>
            <div><span class="text-xs text-mute">Nama</span><div class="font-semibold">{{ $hasil->nama_calon_siswa }}</div></div>
            <div><span class="text-xs text-mute">Status</span><div class="font-semibold">{{ $labelStatus }}</div></div>
        </div>
        @if ($hasil->catatan_verifikasi)
            <p class="text-xs text-mute mt-3">Catatan Tata Usaha: {{ $hasil->catatan_verifikasi }}</p>
        @endif
    </div>
@endif
<div class="grid lg:grid-cols-[1.2fr_1fr] gap-4 items-start">
    <div class="card">
        <h2 class="sec-title">Perjalanan pendaftaran</h2>
        @if (empty($langkah))
            <p class="text-sm text-mute">Masukkan NISN dan kode pendaftaran untuk melihat perjalanan pendaftaran.</p>
        @else
            <ol>
                @foreach ($langkah as $l)
                    @php $dot = ['selesai' => 'bg-ok', 'perhatian' => 'bg-bad', 'belum' => 'bg-line'][$l['state']]; @endphp
                    <li class="relative pl-6 pb-5 border-l-2 border-line ml-1.5 last:pb-0">
                        <span class="absolute -left-[7px] top-1 size-3 rounded-full {{ $dot }}"></span>
                        <div class="font-semibold text-sm {{ $l['state'] === 'belum' ? 'text-mute' : '' }}">{{ $l['judul'] }}</div>
                        <div class="text-xs {{ $l['state'] === 'perhatian' ? 'text-bad' : 'text-mute' }}">{{ $l['ket'] }}</div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    @if ($hasil && in_array($hasil->status_pendaftaran, ['perlu_perbaikan', 'ditolak'], true))
        @php
            $perluDiperbaiki = $berkas
                ->whereIn('status_verifikasi', ['perlu_perbaikan', 'ditolak'])
                ->pluck('jenis_dokumen')
                ->filter()
                ->unique()
                ->values();
        @endphp
        <div class="card border-l-4 !border-l-bad">
            <h2 class="text-lg font-bold">Pengajuan perlu diperbaiki</h2>
            <p class="text-sm text-mute mt-1">
                {{ $hasil->catatan_verifikasi ?: 'Perbaiki data atau berkas sesuai arahan Tata Usaha.' }}
            </p>
            @if ($perluDiperbaiki->isNotEmpty())
                <p class="text-sm mt-2"><span class="font-semibold">Dokumen terkait:</span> {{ $perluDiperbaiki->implode(', ') }}</p>
            @endif
            @if ($bolehPerbaiki)
                <a class="btn btn-flame mt-4 w-full" href="{{ route('ppdb', ['draf' => $hasil->token_draf]) }}">Kirim ulang berkas</a>
                <p class="text-xs text-mute mt-2">Data sebelumnya tetap terisi. Perbaiki bagian yang diminta, lalu kirim kembali untuk diperiksa.</p>
            @elseif (!request()->filled('cek_nisn') || !request()->filled('cek_kode'))
                <p class="text-xs text-mute mt-3">Masukkan NISN dan kode pendaftaran untuk membuka formulir perbaikan.</p>
            @else
                <p class="text-xs text-mute mt-3">Formulir perbaikan belum tersedia. Hubungi Tata Usaha.</p>
            @endif
        </div>
    @endif
</div>
@endsection