@extends('layouts.app')
@section('title', 'Periode Update')
@section('heading', 'Periode Update Data Tahunan')
@section('sub', 'Selama periode dibuka, siswa bisa mengonfirmasi data atau mengajukan perubahan.')
@section('content')
<div class="grid lg:grid-cols-[1fr_1.2fr] gap-4 items-start">
    <form class="card space-y-4" method="POST" action="{{ route('tu.periode.simpan') }}">
        @csrf
        <h2 class="sec-title">Buka periode baru</h2>
        @unless ($tahunAjaran)
            <p class="text-sm text-warn" role="status">Tambahkan tahun ajaran terlebih dahulu sebelum membuka periode.</p>
        @endunless
        <div><label class="label" for="id_tahun_ajaran">Tahun ajaran</label><select class="input" id="id_tahun_ajaran" name="id_tahun_ajaran" data-wajib>
            @foreach ($tahunAjaran as $t)
                <option value="{{ $t['id'] ?? '' }}">{{ $t['nama'] }}</option>
            @endforeach
        </select></div>
        <x-field label="Tanggal buka" name="tgl_buka" type="date" />
        <x-field label="Tanggal tutup" name="tgl_tutup" type="date" />
        <button class="btn btn-flame w-full" @disabled(! $tahunAjaran)>Buka periode</button>
    </form>

    <div class="card">
        <h2 class="sec-title">Riwayat periode</h2>
        @forelse ($periode as $p)
            <div class="py-3 border-b border-line last:border-0 text-sm flex items-center justify-between gap-3">
                <div>
                    <b>{{ $p['tahun'] }}</b>
                    <div class="text-xs text-mute">{{ $p['buka'] }} → {{ $p['tutup'] }}</div>
                    @if ($p['persentase_konfirmasi'] !== null)
                        <div class="text-xs text-mute">Konfirmasi data: {{ number_format($p['persentase_konfirmasi'], 2, ',', '.') }}%</div>
                    @endif
                </div>
                @if ($p['status'] === 'Dibuka')
                    <div class="flex items-center gap-2">
                        <x-badge status="disetujui" class="!bg-ok/15">Dibuka</x-badge>
                        @if (! empty($p['id']))
                            <form method="POST" action="{{ route('tu.periode.tutup', $p['id']) }}" data-konfirmasi="Tutup periode {{ $p['tahun'] }} sekarang?">
                                @csrf
                                <button class="btn btn-ghost !py-1.5">Tutup sekarang</button>
                            </form>
                        @endif
                    </div>
                @else
                    <span class="text-xs font-semibold text-mute">Ditutup</span>
                @endif
            </div>
        @empty
            <p class="py-4 text-sm text-mute">Belum ada periode update yang tercatat.</p>
        @endforelse
    </div>
</div>
@endsection