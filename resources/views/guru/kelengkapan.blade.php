@extends('layouts.app')
@section('title', 'Kelengkapan Data')
@section('heading', 'Kelengkapan Data ' . (($kelas['nama'] ?? null) ? 'Kelas ' . $kelas['nama'] : 'Kelas'))
@section('sub', 'Pantau siapa yang sudah mengonfirmasi atau mengajukan perubahan selama periode update.')
@section('content')
@if (!($semester ?? null))
    <div class="card bg-white !border-warn/40 text-sm text-warn">Belum ada semester aktif.</div>
@elseif (empty($kelasWali))
    <div class="card bg-white text-sm text-mute">Kamu tidak menjadi wali kelas pada semester aktif.</div>
@else
    @if (count($kelasWali) > 1)
        <form method="GET" class="mb-4 flex items-end gap-2" data-auto-filter>
            <div>
                <label class="label" for="kelas">Kelas</label>
                <select class="input !w-56" id="kelas" name="kelas">
                    @foreach ($kelasWali as $k)
                        <option value="{{ $k['id'] }}" @selected(($kelas['id'] ?? 0) == $k['id'])>{{ $k['nama'] }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('guru.kelengkapan') }}" class="btn btn-ghost">Reset</a>
        </form>
    @endif

    @php
        $hitung = collect($siswa)->countBy('status');
        $tanda = [
            'konfirmasi' => ['Sudah konfirmasi', 'bg-ok/15 text-ok'],
            'menunggu'   => ['Menunggu Verifikasi', 'bg-warn/15 text-warn'],
            'disetujui'  => ['Disetujui', 'bg-ok/15 text-ok'],
            'ditolak'    => ['Ditolak', 'bg-bad/15 text-bad'],
            'ubah'       => ['Perlu perubahan', 'bg-warn/15 text-warn'],
            'belum'      => ['Belum merespons', 'bg-bad/15 text-bad'],
        ];
    @endphp

    @if (!($periode ?? null))
        <div class="card mb-4 bg-white text-sm text-mute">Tidak ada periode update yang sedang aktif, sehingga status konfirmasi belum dapat ditampilkan.</div>
    @endif

    <div class="grid grid-cols-3 gap-3 mb-6">
        <x-stat :angka="$hitung['konfirmasi'] ?? 0" label="Sudah konfirmasi" motion />
        <x-stat :angka="($hitung['menunggu'] ?? 0) + ($hitung['disetujui'] ?? 0) + ($hitung['ditolak'] ?? 0) + ($hitung['ubah'] ?? 0)" label="Pengajuan perubahan" motion />
        <x-stat :angka="$hitung['belum'] ?? 0" label="Belum merespons" motion />
    </div>
    <div class="card">
        <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
            <h2 class="font-serif text-lg font-bold">Status per siswa</h2>
            <span class="text-sm text-mute">{{ count($siswa) }} siswa</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[520px]">
                <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Nama</th><th class="p-2">NISN</th><th class="p-2">Status</th><th class="p-2">Tanggal</th></tr></thead>
                <tbody>
                @forelse ($siswa as $s)
                    <tr class="border-b border-line">
                        <td class="p-2.5 font-medium">{{ $s['nama'] }}</td><td class="p-2.5">{{ $s['nisn'] }}</td>
                        <td class="p-2.5"><span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $tanda[$s['status']][1] ?? '' }}">{{ $tanda[$s['status']][0] ?? $s['status'] }}</span></td>
                        <td class="p-2.5 text-mute">{{ $s['tgl'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-4 text-center text-mute">Belum ada siswa aktif di kelas ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
