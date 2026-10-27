
@extends('layouts.app')

@section('title', 'Detail Siswa')
@section('heading', $siswa->nama_lengkap)
@section('sub', 'NISN ' . ($siswa->nisn ?? '-') . ' · NIS ' . ($siswa->nis ?? '-'))

@section('content')
@php
    $jk = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

    $lahir = trim(
        ($siswa->tempat_lahir ?? '') . ', ' . ($siswa->tanggal_lahir ?? ''),
        ', '
    );

    $isian = fn ($baris) => collect(get_object_vars($baris))
        ->reject(fn ($v, $k) => $v === null || $v === '' || str_starts_with($k, 'id_'))
        ->mapWithKeys(fn ($v, $k) => [ucfirst(str_replace('_', ' ', $k)) => $v]);
@endphp

<div class="grid items-start gap-4 lg:grid-cols-2">
    <div class="card">
        <h2 class="sec-title mb-3">Data induk</h2>

        <dl class="grid grid-cols-[130px_1fr] gap-x-3 gap-y-2.5 text-sm">
            @foreach ([
                'NIS' => $siswa->nis,
                'NISN' => $siswa->nisn,
                'NIK' => $siswa->nik,
                'Nama lengkap' => $siswa->nama_lengkap,
                'Jenis kelamin' => $jk[$siswa->jenis_kelamin ?? ''] ?? '-',
                'Tempat lahir' => $siswa->tempat_lahir,
                'Tanggal lahir' => $siswa->tanggal_lahir,
                'Email' => $siswa->email,
                'No. telepon' => $siswa->no_telepon,
                'Alamat' => $alamat->alamat ?? '-',
            ] as $label => $value)
                <dt class="text-mute">{{ $label }}</dt>
                <dd class="font-medium">{{ $value ?: '-' }}</dd>
            @endforeach
        </dl>

        <details class="mt-4 border-t border-dashed border-line pt-3"
            @if ($errors->any()) open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-flame-dark">
                Ubah data
            </summary>

            <form method="POST"
                action="{{ route('tu.siswa.update', $siswa->id_siswa) }}"
                data-validasi
                class="mt-3 space-y-3">
                @csrf
                @method('PUT')

                @foreach ([
                    ['nama_lengkap', 'Nama lengkap', 'text'],
                    ['nis', 'NIS', 'text'],
                    ['nisn', 'NISN', 'text'],
                    ['nik', 'NIK', 'text'],
                    ['tempat_lahir', 'Tempat lahir', 'text'],
                    ['tanggal_lahir', 'Tanggal lahir', 'date'],
                    ['email', 'Email', 'email'],
                    ['no_telepon', 'No. telepon', 'text'],
                ] as [$nama, $label, $tipe])
                    <div>
                        <label class="label" for="u_{{ $nama }}">{{ $label }}</label>
                        <input
                            class="input"
                            id="u_{{ $nama }}"
                            name="{{ $nama }}"
                            type="{{ $tipe }}"
                            value="{{ old($nama, $siswa->{$nama}) }}"
                            @if ($nama === 'nama_lengkap') data-wajib @endif
                            @if ($nama === 'nisn') data-digit="10" inputmode="numeric" maxlength="10" @endif
                            @if ($nama === 'nik') data-digit="16" inputmode="numeric" maxlength="16" @endif
                        >
                        @error($nama)
                            <p class="err mt-1 text-xs text-bad" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div>
                    <label class="label" for="u_jenis_kelamin">Jenis kelamin</label>
                    <select class="input" id="u_jenis_kelamin" name="jenis_kelamin">
                        <option value="">Pilih</option>
                        <option value="L" @selected(old('jenis_kelamin', $siswa->jenis_kelamin) === 'L')>Laki-laki</option>
                        <option value="P" @selected(old('jenis_kelamin', $siswa->jenis_kelamin) === 'P')>Perempuan</option>
                    </select>
                    @error('jenis_kelamin')
                        <p class="err mt-1 text-xs text-bad" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button class="btn btn-flame">Simpan perubahan</button>
            </form>
        </details>
    </div>

    <div class="card">
        <h2 class="sec-title mb-3">Alamat</h2>

        @if ($alamat && $isian($alamat)->isNotEmpty())
            <dl class="grid grid-cols-[130px_1fr] gap-x-3 gap-y-2.5 text-sm">
                @foreach ($isian($alamat) as $k => $v)
                    <dt class="text-mute">{{ $k }}</dt>
                    <dd class="font-medium">{{ $v }}</dd>
                @endforeach
            </dl>
        @else
            <p class="text-sm text-mute">Alamat belum tercatat.</p>
        @endif
    </div>
</div>

<div class="grid items-start gap-4 lg:grid-cols-2 mt-4">
    <div class="card">
        <h2 class="sec-title mb-3">Orang tua / wali</h2>

        @forelse ($orangTua as $ortu)
            <div class="border-b border-line py-2.5 text-sm last:border-0">
                <b>{{ $ortu->hubungan ?? 'Orang tua / wali' }}: {{ $ortu->nama_lengkap ?? '-' }}</b>
                <div class="text-mute mt-1">Telepon: {{ $ortu->no_telepon ?: '-' }}</div>
                <div class="text-mute">Email: {{ $ortu->email ?: '-' }}</div>
                <div class="text-mute">Alamat: {{ $ortu->alamat ?: '-' }}</div>
            </div>
        @empty
            <p class="text-sm text-mute">Data orang tua/wali belum tersedia.</p>
        @endforelse
    </div>

    <div class="card">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="sec-title">Dokumen siswa</h2>
            <a class="btn btn-ghost !py-1.5"
                href="{{ route('tu.siswa.dokumen', $siswa->id_siswa) }}">
                Kelola
            </a>
        </div>

        @forelse ($dokumen as $d)
            <div class="border-b border-line py-2.5 text-sm last:border-0">
                <b>{{ $d->nama_jenis ?? $d->jenis_dokumen ?? 'Dokumen #' . ($d->id_jenis_dokumen ?? '-') }}</b>
                <div class="text-mute mt-1">
                    {{ $d->format_file ?? '-' }}
                    @if (isset($d->ukuran_kb))
                        · {{ $d->ukuran_kb }} KB
                    @endif
                </div>
                <div class="text-xs text-mute">
                    No. {{ $d->nomor_dokumen ?? '-' }}
                    · diunggah {{ $d->tgl_unggah ?? $d->diunggah_pada ?? '-' }}
                    · {{ !empty($d->ada_fisik) ? 'fisik sudah diterima' : 'status fisik belum diketahui atau belum diterima' }}
                </div>
            </div>
        @empty
            <p class="text-sm text-mute">Belum ada dokumen siswa.</p>
        @endforelse
    </div>
</div>

<div class="mt-4 flex flex-wrap items-center justify-between gap-3">
    <a class="btn btn-ghost" href="{{ route('tu.siswa') }}">
        Kembali ke daftar siswa
    </a>

    <form method="POST"
        action="{{ route('tu.siswa.destroy', $siswa->id_siswa) }}"
        data-validasi
        data-konfirmasi="Hapus data siswa ini? Tindakan ini tidak bisa dibatalkan.">
        @csrf
        @method('DELETE')
        <button class="btn btn-ghost !text-bad">Hapus data siswa</button>
    </form>
</div>
@endsection
