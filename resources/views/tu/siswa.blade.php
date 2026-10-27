
@extends('layouts.app')

@section('title', 'Data Siswa')
@section('heading', 'Data Induk Siswa')
@section('sub', 'Seluruh data siswa yang tercatat. Klik nama untuk melihat detail dan dokumen.')

@section('content')
@if (session('success'))
    <div class="card mb-4 text-sm font-semibold text-ok">
        {{ session('success') }}
    </div>
@endif

<div class="card mb-4">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('tu.siswa') }}" class="flex flex-wrap items-center gap-2">
            <input
                class="input !w-64"
                type="search"
                name="cari"
                value="{{ request('cari') }}"
                placeholder="Cari nama / NISN / NIS"
                aria-label="Cari siswa"
            >
            <button class="btn btn-ghost" type="submit">Cari</button>

            @if (request()->filled('cari'))
                <a href="{{ route('tu.siswa') }}" class="text-sm font-semibold text-flame-dark hover:underline">
                    Hapus pencarian
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead>
                <tr class="border-b-2 border-line text-left text-xs text-mute">
                    <th class="p-2">Nama</th>
                    <th class="p-2">NISN</th>
                    <th class="p-2">Kelas</th>
                    <th class="p-2">Status</th>
                    <th class="p-2"></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($daftar as $r)
                    <tr class="border-b border-line transition hover:bg-tint/60">
                        <td class="p-2.5 font-medium">{{ $r['nama'] }}</td>
                        <td class="p-2.5">{{ $r['nisn'] ?? '-' }}</td>
                        <td class="p-2.5">{{ $r['kelas'] ?? '-' }}</td>
                        <td class="p-2.5">{{ $r['status'] ?? '-' }}</td>
                        <td class="p-2.5 text-right">
                            <a
                                class="btn btn-ghost !py-1.5"
                                href="{{ route('tu.siswa.detail', $r['id']) }}"
                            >
                                Buka
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-mute">
                            {{ request()->filled('cari') ? 'Tidak ada siswa yang cocok dengan pencarian.' : 'Belum ada data siswa.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $daftar->links() }}
        </div>
    </div>
</div>

{{-- Form tambah siswa tersambung ke SiswaController@store. --}}
<details class="card" @if ($errors->any()) open @endif>
    <summary class="cursor-pointer font-serif text-lg font-bold text-navy">
        + Tambah siswa
    </summary>

    <form
        method="POST"
        action="{{ route('tu.siswa.store') }}"
        data-validasi
        class="mt-4 space-y-4"
    >
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Nama lengkap" name="nama_lengkap" wajib="1" />
            <x-field label="NIS" name="nis" />
            <x-field label="NISN" name="nisn" digit="10" hint="10 digit" />
            <x-field label="NIK" name="nik" digit="16" hint="16 digit" />

            <div>
                <label class="label" for="jenis_kelamin">Jenis kelamin</label>
                <select class="input" id="jenis_kelamin" name="jenis_kelamin">
                    <option value="">Pilih</option>
                    <option value="L" @selected(old('jenis_kelamin') === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
                </select>
                @error('jenis_kelamin')
                    <p class="err mt-1 text-xs text-bad" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <x-field label="Tempat lahir" name="tempat_lahir" />
            <x-field label="Tanggal lahir" name="tanggal_lahir" type="date" />
            <x-field label="Email" name="email" type="email" />
            <x-field label="No. telepon" name="no_telepon" hp="1" />
        </div>

        <button class="btn btn-flame">Simpan siswa</button>
    </form>
</details>
@endsection
