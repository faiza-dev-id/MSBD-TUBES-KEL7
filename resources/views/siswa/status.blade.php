@extends('layouts.app')
@section('title', 'Status Pendaftaran')
@section('heading', 'Status Pendaftaran')
@section('sub', 'Pantau sampai mana pendaftaranmu diproses. Kalau ada berkas yang dikembalikan, unggah ulang di sini.')
@section('content')
<div class="grid lg:grid-cols-[1.2fr_1fr] gap-4 items-start">
    <div class="card">
        <h2 class="sec-title">Perjalanan pendaftaran</h2>
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
    </div>

    <div class="card border-l-4 !border-l-bad">
        <h2 class="text-lg font-bold">Berkas perlu diunggah ulang</h2>
        <p class="text-sm text-mute mt-1">Kartu Keluarga: berkas buram / tidak terbaca. Foto ulang di tempat terang, pastikan semua tulisan terbaca.</p>
        <label class="flex items-center justify-between gap-3 border-2 border-dashed border-line rounded-lg p-3.5 text-sm cursor-pointer hover:border-flame mt-4">
            <span>Kartu Keluarga</span><span class="btn btn-ghost !py-1.5">Pilih file</span><input type="file" class="hidden">
        </label>
        <button class="btn btn-flame mt-3 w-full">Kirim ulang berkas</button>
    </div>
</div>
@endsection