@extends('layouts.app')
@section('title', 'Validasi Pengajuan Siswa')
@section('heading', 'Validasi Pengajuan Perubahan Data')
@section('sub', 'Periksa perubahan data siswa di kelas yang kamu walikan, lalu setujui atau tolak pengajuan.')
@section('content')
@if (!($semester ?? null))
    <div class="card bg-white !border-warn/40 text-sm text-warn">Belum ada semester aktif.</div>
@elseif (empty($kelasWali))
    <div class="card bg-white text-sm text-mute">Kamu tidak menjadi wali kelas pada semester aktif.</div>
@else
    @if (count($kelasWali) > 1)
        <form method="GET" class="mb-4 flex items-end gap-2">
            <div>
                <label class="label" for="kelas">Kelas</label>
                <select class="input !w-56" id="kelas" name="kelas">
                    @foreach ($kelasWali as $k)
                        <option value="{{ $k['id'] }}" @selected(($kelas['id'] ?? 0) == $k['id'])>{{ $k['nama'] }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-ghost">Tampilkan</button>
        </form>
    @endif

    <div class="mb-3 flex items-center justify-between gap-3 flex-wrap">
        <h2 class="sec-title mb-0">Pengajuan siswa {{ $kelas['nama'] ?? '' }}</h2>
        <span class="text-sm text-mute">{{ count($pengajuan) }} pengajuan</span>
    </div>
    <div class="space-y-3">
        @forelse ($pengajuan as $p)
            <article class="card">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <div class="text-xs text-mute">{{ $p['tgl'] }} · {{ $p['nisn'] }}</div>
                        <h3 class="font-semibold">{{ $p['siswa'] }}</h3>
                    </div>
                    <x-badge :status="$p['status']" />
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($p['detail'] as $detail)
                        <div class="grid sm:grid-cols-2 gap-3 text-sm">
                            <div class="sm:col-span-2 font-semibold">{{ $detail['field'] }}</div>
                            <div class="rounded-md bg-bad/10 p-3"><div class="mb-1 text-xs text-mute">Data lama</div>{{ $detail['lama'] ?? '-' }}</div>
                            <div class="rounded-md bg-ok/10 p-3"><div class="mb-1 text-xs text-mute">Data baru</div>{{ $detail['baru'] ?? '-' }}</div>
                            @if ($detail['bukti'])
                                <a class="text-sm font-semibold text-flame hover:underline sm:col-span-2"
                                   href="{{ route('guru.pengajuan.bukti', [$p['id'], $detail['id']]) }}">Unduh bukti pendukung</a>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($p['catatan'])
                    <p class="mt-3 text-sm text-mute">Catatan validasi: {{ $p['catatan'] }}</p>
                @endif

                @if ($p['status'] === 'menunggu')
                    <form class="mt-4 border-t border-line pt-4" method="POST" action="{{ route('guru.pengajuan.simpan') }}">
                        @csrf
                        <input type="hidden" name="id_pengajuan" value="{{ $p['id'] }}">
                        <label class="label" for="cat-{{ $p['id'] }}">Catatan untuk siswa <span class="font-normal">(opsional)</span></label>
                        <textarea class="input" id="cat-{{ $p['id'] }}" name="catatan" rows="2"></textarea>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button class="btn" name="keputusan" value="setujui">Setujui</button>
                            <button class="btn btn-ghost !text-bad" name="keputusan" value="tolak">Tolak</button>
                        </div>
                    </form>
                @endif
            </article>
        @empty
            <div class="card text-center text-sm text-mute">Belum ada pengajuan dari siswa kelas ini.</div>
        @endforelse
    </div>
@endif
@endsection