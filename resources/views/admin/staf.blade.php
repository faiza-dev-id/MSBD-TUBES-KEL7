@extends('layouts.app')
@section('title', 'Guru & Staf')
@section('heading', 'Data Guru dan Staf')
@section('sub', 'Data pegawai sekolah. Untuk mengubah data atau membuat akun, gunakan menu Pengguna.')
@section('content')
<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
    <div><label class="label">Cari</label>
        <input class="input !w-64" name="cari" value="{{ $cari }}" placeholder="Nama, NIP/jabatan, atau username"></div>
    <button class="btn btn-flame">Cari</button>
    @if ($cari !== '') <a href="{{ route('admin.staf') }}" class="btn btn-ghost">Reset</a> @endif
</form>
<div class="space-y-4">
    <div class="card">
        <h2 class="font-serif text-lg font-bold mb-3">Data Guru <span class="text-sm font-normal text-mute">({{ $guru->count() }})</span></h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[560px]">
                <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Nama</th><th class="p-2">NIP</th><th class="p-2">No. HP</th><th class="p-2">Username</th><th class="p-2">Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($guru as $g)
                    <tr class="border-b border-line">
                        <td class="p-2.5 font-medium">{{ $g->nama_guru }}</td><td class="p-2.5">{{ $g->nip ?: '-' }}</td>
                        <td class="p-2.5">{{ $g->no_telepon ?: '-' }}</td><td class="p-2.5">{{ $g->username ?: '-' }}</td>
                        <td class="p-2.5"><span class="text-xs font-semibold {{ $g->is_aktif ? 'text-ok' : 'text-mute' }}">{{ $g->is_aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="p-2.5 text-right">@if ($g->id_pengguna)<a class="btn btn-ghost !py-1.5" href="{{ route('admin.pengguna.edit', $g->id_pengguna) }}">Ubah</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-mute">Tidak ada data guru.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h2 class="font-serif text-lg font-bold mb-3">Data Staf <span class="text-sm font-normal text-mute">({{ $staf->count() }})</span></h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[560px]">
                <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Nama</th><th class="p-2">Jabatan</th><th class="p-2">No. HP</th><th class="p-2">Username</th><th class="p-2">Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($staf as $s)
                    <tr class="border-b border-line">
                        <td class="p-2.5 font-medium">{{ $s->nama_staf }}</td><td class="p-2.5">{{ $s->jabatan ?: '-' }}</td>
                        <td class="p-2.5">{{ $s->no_telepon ?: '-' }}</td><td class="p-2.5">{{ $s->username ?: '-' }}</td>
                        <td class="p-2.5"><span class="text-xs font-semibold {{ $s->is_aktif ? 'text-ok' : 'text-mute' }}">{{ $s->is_aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="p-2.5 text-right">@if ($s->id_pengguna)<a class="btn btn-ghost !py-1.5" href="{{ route('admin.pengguna.edit', $s->id_pengguna) }}">Ubah</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-mute">Tidak ada data staf.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
