
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
        ->reject(fn ($v, $k) => $v === null || $v === '' || $k === 'negara' || str_starts_with($k, 'id_') || in_array($k, ['token_draf', 'dibuat_pada', 'diubah_pada', 'created_at', 'updated_at'], true))
        ->mapWithKeys(fn ($v, $k) => [ucfirst(str_replace('_', ' ', $k)) => \App\Support\Referensi\OpsiPpdb::label($k, $v)]);
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

@if ($ppdb)
    <div class="card mt-4">
        <h2 class="sec-title mb-3">Data formulir PPDB</h2>
        @foreach ([
            'Data pendaftaran' => $ppdb,
            'Alamat saat pendaftaran' => $ppdbAlamat,
            'Data periodik' => $ppdbPeriodik,
            'Data keluar sekolah sebelumnya' => $ppdbKeluar,
        ] as $judul => $baris)
            @if ($baris && $isian($baris)->isNotEmpty())
                <section class="mb-4 last:mb-0">
                    <h3 class="mb-2 text-sm font-bold text-navy">{{ $judul }}</h3>
                    <dl class="grid gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                        @foreach ($isian($baris) as $k => $v)
                            <div>
                                <dt class="text-xs text-mute">{{ $k }}</dt>
                                <dd class="font-medium">{{ is_bool($v) ? ($v ? 'Ya' : 'Tidak') : $v }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif
        @endforeach

        @if ($ppdbOrangTua->isNotEmpty())
            <section class="mb-4">
                <h3 class="mb-2 text-sm font-bold text-navy">Data orang tua / wali PPDB</h3>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($ppdbOrangTua as $orang)
                        <div class="rounded-xl border border-line p-3">
                            <b class="mb-2 block text-sm">{{ ucfirst($orang->hubungan) }}</b>
                            <dl class="grid gap-x-3 gap-y-1.5 text-sm sm:grid-cols-2">
                                @foreach ($isian($orang) as $k => $v)
                                    <div>
                                        <dt class="text-xs text-mute">{{ $k }}</dt>
                                        <dd class="font-medium">{{ is_bool($v) ? ($v ? 'Ya' : 'Tidak') : $v }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @foreach (['Prestasi' => $ppdbPrestasi, 'Beasiswa' => $ppdbBeasiswa] as $judul => $daftar)
            @if ($daftar->isNotEmpty())
                <section class="mb-4 last:mb-0">
                    <h3 class="mb-2 text-sm font-bold text-navy">Data {{ strtolower($judul) }}</h3>
                    <div class="space-y-2">
                        @foreach ($daftar as $baris)
                            <dl class="grid gap-x-4 gap-y-2 rounded-xl border border-line p-3 text-sm sm:grid-cols-2">
                                @foreach ($isian($baris) as $k => $v)
                                    <div>
                                        <dt class="text-xs text-mute">{{ $k }}</dt>
                                        <dd class="font-medium">{{ is_bool($v) ? ($v ? 'Ya' : 'Tidak') : $v }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
@else
    <div class="card mt-4 text-sm text-mute">Data formulir PPDB belum ditemukan untuk NISN ini.</div>
@endif

<div class="grid items-start gap-4 lg:grid-cols-2 mt-4">
    <div class="card">
        <h2 class="sec-title mb-3">Orang tua / wali</h2>

        @forelse ($orangTua as $ortu)
            <div class="border-b border-line py-2.5 text-sm last:border-0">
                <b>{{ $ortu->hubungan ?? 'Orang tua / wali' }}: {{ $ortu->nama_lengkap ?? '-' }}</b>
                <div class="text-mute mt-1">Telepon: {{ $ortu->no_telepon ?: '-' }}</div>
                @if (!in_array(mb_strtolower((string) $ortu->hubungan), ['ayah', 'ibu'], true))
                    <div class="text-mute">Email: {{ $ortu->email ?: '-' }}</div>
                @endif
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
            @php
                $catatan = (string) ($d->catatan ?? '');
                $fisik = str_contains($catatan, 'fisik sudah diterima');
                $nomor = trim(str_replace('· fisik sudah diterima', '', $catatan));
                $nomor = trim($nomor, " \t\n\r\0\x0B·");
                $format = strtoupper(pathinfo((string) ($d->path_file ?? ''), PATHINFO_EXTENSION) ?: '-');
                $ukuran = $d->ukuran ?? '-';

                $diunggah = $d->diunggah_pada
                    ? \Illuminate\Support\Carbon::parse($d->diunggah_pada)->locale('id')->translatedFormat('d M Y')
                    : '-';
            @endphp
            <div class="border-b border-line py-2.5 text-sm last:border-0">
                <b>{{ $d->jenis_dokumen ?? 'Dokumen' }}</b>
                <div class="text-mute mt-1">
                    {{ $format }}@if ($ukuran !== '-') · {{ $ukuran }} @endif
                </div>
                <div class="text-xs text-mute">
                    No. {{ $nomor !== '' ? $nomor : '-' }}
                    · diunggah {{ $diunggah }}
                    · {{ $fisik ? 'fisik sudah diterima' : 'fisik belum diterima' }}
                </div>
                @if (!empty($d->id_dokumen))
                    <div class="mt-2">
                        <a href="{{ route('tu.dokumen.preview', ['idDokumen' => $d->id_dokumen]) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-flame-dark hover:underline">Lihat berkas</a>
                    </div>
                @endif
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
</div>
@endsection
