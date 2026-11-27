@extends('layouts.app')
@section('title', 'Konfigurasi Akademik')
@section('heading', 'Konfigurasi Akademik')
@section('sub', 'Tahun ajaran, semester, kelas, mata pelajaran, komponen penilaian, dan skala predikat.')
@section('content')
@php
    $tampil = function ($row, $kol) {
        $v = $row->{$kol[1]} ?? null;
        $fmt = $kol[2] ?? null;
        if ($fmt === 'aktif') return $v ? 'Aktif' : 'Nonaktif';
        if ($v === null || $v === '') return '-';
        if ($fmt === 'date') return \Carbon\Carbon::parse($v)->locale('id')->translatedFormat('d M Y');
        if ($fmt === 'num') return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        return $v;
    };
@endphp
<div class="flex gap-1.5 flex-wrap mb-4 text-sm">
    @foreach ($tabs as $k => $judul)
        <a href="{{ route('akademik.konfigurasi', $k) }}" class="px-3.5 py-1.5 rounded-full border {{ $k === $tab ? 'bg-navy text-paper border-navy' : 'border-line text-navy hover:bg-card' }}">{{ $judul }}</a>
    @endforeach
</div>

<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3" data-auto-filter>
    <div><label class="label">Cari</label><input class="input !w-64" name="cari" value="{{ $cari }}" placeholder="Ketik untuk mencari"></div>
    @if ($cari !== '') <a href="{{ route('akademik.konfigurasi', $tab) }}" class="btn btn-ghost">Reset</a> @endif
</form>

@if ($tab === 'predikat')
    @php
        $rentangForm = old('rentang', $rentangPredikat->map(fn ($r) => [
            'id_predikat' => $r->id_predikat,
            'huruf' => $r->huruf,
            'nilai_min' => $r->nilai_min,
            'nilai_maks' => $r->nilai_maks,
            'deskripsi' => $r->deskripsi,
        ])->all());
        $rentangForm = array_pad($rentangForm, count($rentangForm) + 4, []);
    @endphp
    <form method="POST" action="{{ route('akademik.konfigurasi.predikat.simpan') }}" class="card space-y-4">
        @csrf @method('PUT')
        <p class="text-sm text-mute">Atur semua rentang dalam satu kali simpan. Rentang wajib berurutan dari 0 sampai 100 tanpa tumpang-tindih atau celah. Predikat yang telah dipakai pada rapor tidak dapat dihapus.</p>
        <div class="space-y-3">
            @foreach ($rentangForm as $i => $nilai)
                <div class="grid gap-3 sm:grid-cols-5 items-end border-b border-line pb-3">
                    <input type="hidden" name="rentang[{{ $i }}][id_predikat]" value="{{ $nilai['id_predikat'] ?? '' }}">
                    <div><label class="label">Predikat</label><input class="input" name="rentang[{{ $i }}][huruf]" value="{{ $nilai['huruf'] ?? '' }}" maxlength="5"></div>
                    <div><label class="label">Nilai minimum</label><input class="input" type="number" step="0.01" min="0" max="100" name="rentang[{{ $i }}][nilai_min]" value="{{ $nilai['nilai_min'] ?? '' }}"></div>
                    <div><label class="label">Nilai maksimum</label><input class="input" type="number" step="0.01" min="0" max="100" name="rentang[{{ $i }}][nilai_maks]" value="{{ $nilai['nilai_maks'] ?? '' }}"></div>
                    <div><label class="label">Deskripsi</label><input class="input" name="rentang[{{ $i }}][deskripsi]" value="{{ $nilai['deskripsi'] ?? '' }}" maxlength="255"></div>
                    @if (!empty($nilai['id_predikat']))
                        <label class="flex items-center gap-2 text-sm text-bad"><input type="checkbox" name="rentang[{{ $i }}][hapus]" value="1" @checked(!empty($nilai['hapus']))> Hapus</label>
                    @endif
                </div>
            @endforeach
        </div>
        <button class="btn btn-flame">Simpan skala</button>
    </form>
@else
    <details class="card mb-4" @if ($errors->any() && old('_tambah')) open @endif>
        <summary class="cursor-pointer font-semibold">+ Tambah {{ $sp['judul'] }}</summary>
        <form method="POST" action="{{ route('akademik.konfigurasi.simpan', $tab) }}" class="grid gap-3 sm:grid-cols-2 items-end mt-3">
            @csrf
            <input type="hidden" name="_tambah" value="1">
            @foreach ($sp['fields'] as $f)
                @include('akademik.konfigurasi-field', ['f' => $f, 'nilai' => old($f['name'], $f['type'] === 'bool' && $f['name'] === 'is_aktif' && in_array($tab, ['kelas', 'mapel'], true) ? true : null), 'opsi' => $opsi, 'edit' => false])
            @endforeach
            <div><button class="btn btn-flame">Simpan</button></div>
        </form>
    </details>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[560px]">
                <thead>
                    <tr class="text-left text-xs text-mute border-b-2 border-line">
                        @foreach ($sp['kolom'] as $kol) <th class="p-2">{{ $kol[0] }}</th> @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($baris as $row)
                    @php $id = $row->{$sp['pk']}; @endphp
                    <tr class="border-b border-line">
                        @foreach ($sp['kolom'] as $i => $kol)
                            <td class="p-2.5 {{ $i === 0 ? 'font-medium' : '' }}">{{ $tampil($row, $kol) }}</td>
                        @endforeach
                        <td class="p-2.5 text-right whitespace-nowrap">
                            <button type="button" class="btn btn-ghost !py-1.5 js-ubah">Ubah</button>
                            <form method="POST" action="{{ route('akademik.konfigurasi.hapus', [$tab, $id]) }}" class="inline" onsubmit="return confirm('Hapus data ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost !py-1.5 !text-bad">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <tr class="hidden border-b border-line bg-paper js-edit">
                        <td colspan="{{ count($sp['kolom']) + 1 }}" class="p-3">
                            <form method="POST" action="{{ route('akademik.konfigurasi.ubah', [$tab, $id]) }}" class="grid gap-3 sm:grid-cols-2 items-end">
                                @csrf @method('PUT')
                                @foreach ($sp['fields'] as $f)
                                    @include('akademik.konfigurasi-field', ['f' => $f, 'nilai' => $row->{$f['name']}, 'opsi' => $opsi, 'edit' => true])
                                @endforeach
                                <div class="flex gap-2">
                                    <button class="btn btn-flame">Simpan</button>
                                    <button type="button" class="btn btn-ghost js-batal">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($sp['kolom']) + 1 }}" class="p-6 text-center text-mute">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $baris->links() }}</div>
    </div>
@endif

<script>
document.querySelectorAll('.js-ubah').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('tr').nextElementSibling.classList.toggle('hidden'); });
});
document.querySelectorAll('.js-batal').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('tr').classList.add('hidden'); });
});
</script>
@endsection
