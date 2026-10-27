@extends('layouts.app')
@section('title', 'Dokumen Siswa')
@section('heading', 'Dokumen siswa')
@section('sub', ($nama ?? 'Siswa #' . $id) . ' · tambah, ganti, atau nonaktifkan dokumen.')
@section('content')
<div class="mb-4"><a href="{{ route('tu.siswa.detail', $id) }}" class="text-sm font-semibold text-flame-dark hover:underline">&larr; Kembali ke detail siswa</a></div>

<div class="grid items-start gap-4 lg:grid-cols-[1.3fr_1fr]">
    <div class="card">
        <h2 class="sec-title">Dokumen tercatat</h2>
        @foreach ($dokumen as $d)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line py-3 text-sm last:border-0">
                <div class="min-w-0">
                    <b>{{ $d['jenis'] }}</b> <span class="text-mute">· {{ $d['format'] }}, {{ $d['ukuran'] }}</span>
                    <div class="text-xs text-mute">No. {{ $d['nomor'] }} · diunggah {{ $d['tgl'] }} · {{ $d['fisik'] ? 'fisik sudah diterima' : 'fisik belum diterima' }}</div>
                </div>
                {{-- TODO backend: isi action dengan route nonaktifkan dokumen (PATCH) lalu hapus onsubmit. Field: id_dokumen --}}
                <form method="POST" action="#" onsubmit="return false" data-validasi data-konfirmasi="Nonaktifkan dokumen {{ $d['jenis'] }}?">
                    @csrf @method('PATCH')
                    <input type="hidden" name="id_dokumen" value="{{ $loop->iteration }}">
                    <button class="btn btn-ghost !py-1.5 !text-bad">Nonaktifkan</button>
                </form>
            </div>
        @endforeach
    </div>

    {{-- TODO backend: isi action dengan route unggah dokumen (POST, multipart) lalu hapus onsubmit. Field: id_siswa, id_jenis_dokumen, nomor_dokumen, berkas, ada_fisik --}}
    <form class="card space-y-4" method="POST" action="#" onsubmit="return false" enctype="multipart/form-data" data-validasi>
        @csrf
        <h2 class="sec-title">Tambah atau ganti dokumen</h2>
        <input type="hidden" name="id_siswa" value="{{ $id }}">
        <div>
            <label class="label" for="id_jenis_dokumen">Jenis dokumen</label>
            {{-- value masih nama jenis; ganti ke id_jenis_dokumen dari database --}}
            <select class="input" id="id_jenis_dokumen" name="id_jenis_dokumen" data-wajib>
                <option value="">Pilih jenis</option>
                @foreach ($jenis as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
            </select>
        </div>
        <x-field label="Nomor dokumen" name="nomor_dokumen" hint="Kosongkan jika tidak ada nomornya" />
        <div>
            <label class="label" for="berkas">Berkas</label>
            <input class="input" type="file" id="berkas" name="berkas" accept=".pdf,.jpg,.jpeg,.png" data-wajib>
            <p class="mt-1 text-[11px] text-mute">PDF, JPG, atau PNG. Maksimal 2 MB.</p>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ada_fisik" value="1" class="size-4 accent-flame"> Dokumen fisik sudah diterima sekolah</label>
        <button class="btn btn-flame w-full">Simpan dokumen</button>
    </form>
</div>
@endsection