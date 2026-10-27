@extends('layouts.app')
@section('title', 'Persetujuan Data')
@section('heading', 'Persetujuan Perubahan Data')
@section('sub', 'Bandingkan data lama dan baru, cek bukti, lalu setujui, kembalikan, atau tolak. Setiap keputusan tercatat di jejak audit.')
@section('content')
<div class="space-y-3">
    @foreach ($pengajuan as $p)
        <div class="card">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <div class="text-xs text-mute">{{ $p['id'] }} · Contoh Siswa · {{ $p['tgl'] }}</div>
                    <h2 class="font-serif text-lg font-bold">Ubah {{ $p['field'] }}</h2>
                </div>
                <x-badge :status="$p['status']" />
            </div>
            <div class="grid sm:grid-cols-2 gap-3 mt-3 text-sm">
                <div class="bg-bad/10 rounded-md p-3"><div class="text-xs text-mute mb-1">Data lama</div>{{ $p['lama'] }}</div>
                <div class="bg-ok/10 rounded-md p-3"><div class="text-xs text-mute mb-1">Data baru</div>{{ $p['baru'] }}</div>
            </div>
            @if ($p['status'] === 'menunggu')
                {{-- TODO backend: isi action dengan route keputusan (POST) lalu hapus onsubmit. --}}
                <form method="POST" action="#" onsubmit="return false">
                    @csrf
                    <input type="hidden" name="id_pengajuan" value="{{ $p['id'] }}">
                    <div class="mt-4">
                        <label class="label" for="cat-{{ $p['id'] }}">Catatan untuk siswa <span class="font-normal">(isi jika dikembalikan atau ditolak)</span></label>
                        <textarea class="input" id="cat-{{ $p['id'] }}" name="catatan" rows="2"></textarea>
                    </div>
                    <div class="flex gap-2 mt-3 flex-wrap">
                        <button class="btn" name="keputusan" value="setujui">Setujui</button>
                        <button class="btn btn-ghost" name="keputusan" value="kembalikan">Kembalikan</button>
                        <button class="btn btn-ghost !text-bad" name="keputusan" value="tolak">Tolak</button>
                        <a href="#" class="btn btn-ghost">Lihat bukti</a>
                    </div>
                </form>
                <p class="text-[11px] text-mute mt-2"><b>Kembalikan</b>: siswa diminta memperbaiki data atau bukti, lalu mengajukan lagi.</p>
            @endif
        </div>
    @endforeach
</div>
@endsection