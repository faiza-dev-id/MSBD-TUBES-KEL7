@extends('layouts.app')
@section('title', 'Pengguna')
@section('heading', 'Manajemen Pengguna')
@section('sub', 'Buat, ubah, aktifkan, nonaktifkan, atau hapus akun. Login gagal beruntun akan dikunci otomatis. Reset sandi siswa dilakukan Tata Usaha.')
@section('content')
<div class="card">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
        <form method="GET"
    action="{{ route('admin.pengguna') }}"
    class="flex items-center gap-2 flex-wrap" data-auto-filter>

    <input
        type="text"
        name="cari"
        class="input !w-64"
        placeholder="Cari nama / username / peran"
        value="{{ $cari ?? '' }}"
    >

    @if (!empty($cari))
        <a href="{{ route('admin.pengguna') }}" class="btn btn-ghost">
            Reset
        </a>
    @endif
</form>
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
                        <a href="{{ route('admin.pengguna.edit', $u['id_pengguna']) }}"
                            class="btn btn-ghost !py-1.5">
                            Ubah
                        </a>
                        <form method="POST"
                            action="{{ route('admin.pengguna.status', $u['id_pengguna']) }}"
                            class="inline"
                            data-validasi
                            data-konfirmasi="{{ $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }} akun ini?">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                class="btn btn-ghost !py-1.5 {{ $u['aktif'] ? '!text-bad' : '' }}">
                                {{ $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <button type="button"
                            class="btn btn-ghost !py-1.5 !text-bad"
                            onclick="this.nextElementSibling.showModal()">
                            Hapus
                        </button>
                        <dialog class="m-auto w-[min(92vw,28rem)] rounded-xl border border-line bg-card p-0 text-ink backdrop:bg-black/40">
                            <div class="p-5">
                                <h2 class="text-lg font-semibold">Hapus akun secara permanen?</h2>
                                <p class="mt-2 text-sm text-mute">Akun {{ $u['username'] }} akan dihapus dan tidak dapat dipulihkan.</p>
                                <form method="POST" action="{{ route('admin.pengguna.hapus', $u['id_pengguna']) }}" class="mt-5 flex justify-end gap-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Batal</button>
                                    <button type="submit" class="btn btn-flame">Hapus permanen</button>
                                </form>
                            </div>
                        </dialog>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection