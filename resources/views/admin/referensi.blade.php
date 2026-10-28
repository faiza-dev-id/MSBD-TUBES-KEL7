@extends('layouts.app')
@section('title', 'Data Referensi')
@section('heading', 'Data Referensi')
@section('sub', 'Daftar pilihan yang dipakai di formulir, seperti agama, pekerjaan, dan sekolah asal. Perubahan tercatat di jejak audit.')
@section('content')
<div class="flex gap-1.5 flex-wrap mb-4 text-sm">
    @foreach ($tabs as $k => $judul)
        <a href="{{ route('admin.referensi', $k) }}" class="px-3.5 py-1.5 rounded-full border {{ $k === $tab ? 'bg-navy text-paper border-navy' : 'border-line text-navy hover:bg-card' }}">{{ $judul }}</a>
    @endforeach
</div>

@if (session('success'))
    <div class="card mb-4 text-sm text-ok font-semibold">{{ session('success') }}</div>
@endif
@if (session('galat') || $errors->any())
    <div class="card mb-4 text-sm text-bad">{{ session('galat') ?? $errors->first() }}</div>
@endif

<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
    <div><label class="label">Cari</label><input class="input !w-64" name="cari" value="{{ $cari }}" placeholder="Ketik lalu tekan Cari"></div>
    <button class="btn btn-flame">Cari</button>
    @if ($cari !== '') <a href="{{ route('admin.referensi', $tab) }}" class="btn btn-ghost">Reset</a> @endif
</form>

@if ($tab === 'wilayah')
    <div class="card">
        <p class="text-xs text-mute mb-3">Data wilayah bersifat baku dan hanya dibaca di sini.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Provinsi</th><th class="p-2">Kota/Kabupaten</th><th class="p-2">Kecamatan</th><th class="p-2">Kelurahan</th><th class="p-2">Kode pos</th></tr></thead>
                <tbody>
                @forelse ($baris as $b)
                    <tr class="border-b border-line"><td class="p-2.5">{{ $b->nama_provinsi }}</td><td class="p-2.5">{{ $b->nama_kota }}</td><td class="p-2.5">{{ $b->nama_kecamatan }}</td><td class="p-2.5 font-medium">{{ $b->nama_kelurahan }}</td><td class="p-2.5">{{ $b->kode_pos ?: '-' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-mute">Belum ada data wilayah.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $baris->links() }}</div>
    </div>
@else
    <details class="card mb-4" @if ($errors->any() && old('_tambah')) open @endif>
        <summary class="cursor-pointer font-semibold">+ Tambah {{ $def['judul'] }}</summary>
        <form method="POST" action="{{ route('admin.referensi.simpan', $tab) }}" class="grid gap-3 sm:grid-cols-2 items-end mt-3">
            @csrf
            <input type="hidden" name="_tambah" value="1">
            @foreach ($def['fields'] as $f)
                @include('admin.referensi-field', ['f' => $f, 'nilai' => old($f['name'], $f['type'] === 'bool' ? true : null), 'opsi' => $opsi, 'edit' => false])
            @endforeach
            <div><button class="btn btn-flame">Simpan</button></div>
        </form>
    </details>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[480px]">
                <thead>
                    <tr class="text-left text-xs text-mute border-b-2 border-line">
                        @foreach ($def['fields'] as $f) <th class="p-2">{{ $f['label'] }}</th> @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($baris as $row)
                    @php $id = $row->{$def['pk']}; @endphp
                    <tr class="border-b border-line">
                        @foreach ($def['fields'] as $i => $f)
                            @php $v = $row->{$f['name']}; @endphp
                            <td class="p-2.5 {{ $i === 0 ? 'font-medium' : '' }}">
                                @if ($f['type'] === 'bool') {{ $v ? 'Ya' : 'Tidak' }}
                                @elseif ($f['type'] === 'select') {{ $opsi[$f['name']][$v] ?? '-' }}
                                @else {{ $v !== null && $v !== '' ? $v : '-' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="p-2.5 text-right whitespace-nowrap">
                            <button type="button" class="btn btn-ghost !py-1.5 js-ubah">Ubah</button>
                            @empty($def['tanpa_hapus'])
                                <form method="POST" action="{{ route('admin.referensi.hapus', [$tab, $id]) }}" class="inline" onsubmit="return confirm('Hapus data ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost !py-1.5 !text-bad">Hapus</button>
                                </form>
                            @endempty
                        </td>
                    </tr>
                    <tr class="hidden border-b border-line bg-paper js-edit">
                        <td colspan="{{ count($def['fields']) + 1 }}" class="p-3">
                            <form method="POST" action="{{ route('admin.referensi.ubah', [$tab, $id]) }}" class="grid gap-3 sm:grid-cols-2 items-end">
                                @csrf @method('PUT')
                                @foreach ($def['fields'] as $f)
                                    @include('admin.referensi-field', ['f' => $f, 'nilai' => $row->{$f['name']}, 'opsi' => $opsi, 'edit' => true])
                                @endforeach
                                <div class="flex gap-2">
                                    <button class="btn btn-flame">Simpan</button>
                                    <button type="button" class="btn btn-ghost js-batal">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($def['fields']) + 1 }}" class="p-6 text-center text-mute">Belum ada data.</td></tr>
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
@endif
@endsection
