@extends('layouts.app')
@section('title', 'Periode Update')
@section('heading', 'Periode Update Data Tahunan')
@section('sub', 'Selama periode dibuka, siswa bisa mengonfirmasi data atau mengajukan perubahan.')
@section('content')
<div class="grid lg:grid-cols-[1fr_1.2fr] gap-4 items-start">
    <form class="card space-y-4" onsubmit="return false">
        <h2 class="sec-title">Buka periode baru</h2>
        <div><label class="label">Tahun ajaran</label><select class="input"><option>2026/2027</option><option>2027/2028</option></select></div>
        <x-field label="Tanggal buka" name="tgl_buka" type="date" />
        <x-field label="Tanggal tutup" name="tgl_tutup" type="date" />
        <button class="btn btn-flame w-full">Buka periode</button>
    </form>

    <div class="card">
        <h2 class="sec-title">Riwayat periode</h2>
        @foreach ($periode as $p)
            <div class="py-3 border-b border-line last:border-0 text-sm flex items-center justify-between gap-3">
                <div><b>{{ $p['tahun'] }}</b><div class="text-xs text-mute">{{ $p['buka'] }} → {{ $p['tutup'] }}</div></div>
                @if ($p['status'] === 'Dibuka')
                    <div class="flex items-center gap-2"><x-badge status="disetujui" class="!bg-ok/15">Dibuka</x-badge><button class="btn btn-ghost !py-1.5">Tutup sekarang</button></div>
                @else
                    <span class="text-xs font-semibold text-mute">Ditutup</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection