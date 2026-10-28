@extends('layouts.app')
@section('title', 'Konfigurasi Akademik')
@section('heading', 'Konfigurasi Akademik')
@section('sub', 'Tahun ajaran, semester, kelas, mata pelajaran, dan aturan penilaian. Perubahan tercatat di jejak audit.')
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

@if (session('success'))
    <div class="card mb-4 text-sm text-ok font-semibold">{{ session('success') }}</div>
@endif
@if (session('galat') || $errors->any())
    <div class="card mb-4 text-sm text-bad">{{ session('galat') ?? $errors->first() }}</div>
@endif
@if ($bobot)
    <div class="card mb-4 text-sm {{ $bobot['lengkap'] ? 'text-ok' : 'text-bad' }} font-semibold">
        Total bobot saat ini: {{ rtrim(rtrim(number_format($bobot['total_bobot'], 2, '.', ''), '0'), '.') }}%
        {{ $bobot['lengkap'] ? '(sudah 100%)' : '(harus tepat 100% sebelum nilai akhir dihitung)' }}
    </div>
@endif

<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
    <div><label class="label">Cari</label><input class="input !w-64" name="cari" value="{{ $cari }}" placeholder="Ketik lalu tekan Cari"></div>
    <button class="btn btn-flame">Cari</button>
    @if ($cari !== '') <a href="{{ route('akademik.konfigurasi', $tab) }}" class="btn btn-ghost">Reset</a> @endif
</form>

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

<script>
document.querySelectorAll('.js-ubah').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('tr').nextElementSibling.classList.toggle('hidden'); });
});
document.querySelectorAll('.js-batal').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('tr').classList.add('hidden'); });
});
</script>
@endsection
