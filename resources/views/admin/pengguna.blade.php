@extends('layouts.app')
@section('title', 'Pengguna')
@section('heading', 'Manajemen Pengguna')
@section('sub', 'Buat akun, ubah, dan aktifkan atau nonaktifkan akun. Login gagal beruntun akan dikunci otomatis. Reset sandi siswa dilakukan Tata Usaha.')
@section('content')
<div class="card">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
        <input class="input !w-64" placeholder="Cari nama / username">
        <a href="{{ route('admin.pengguna.tambah') }}" class="btn btn-flame">+ Tambah pengguna</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[680px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Nama</th><th class="p-2">Username</th><th class="p-2">Peran</th><th class="p-2">Login terakhir</th><th class="p-2">Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($pengguna as $u)
                <tr class="border-b border-line">
                    <td class="p-2.5 font-medium">{{ $u['nama'] }}</td><td class="p-2.5">{{ $u['username'] }}</td><td class="p-2.5">{{ $u['role'] }}</td><td class="p-2.5 text-mute">{{ $u['login'] }}</td>
                    <td class="p-2.5"><span class="text-xs font-semibold {{ $u['aktif'] ? 'text-ok' : 'text-mute' }}">{{ $u['aktif'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="p-2.5 text-right whitespace-nowrap">
                        <button type="button" class="btn btn-ghost !py-1.5">Ubah</button>
                        <button type="button" class="btn btn-ghost !py-1.5 {{ $u['aktif'] ? '!text-bad' : '' }}"
                                onclick="if (confirm('{{ $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }} akun ini?')) alert('Tampilan rancangan: belum tersimpan ke database.')">{{ $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection