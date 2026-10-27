@extends('layouts.app')
@section('title', 'Pengesahan Kelulusan')
@section('heading', 'Pengesahan kelulusan PPDB')
@section('sub', 'Pendaftar yang berkasnya sudah diterima. Isi nilai ujian seleksi, lalu sahkan.')
@section('content')
<div class="mb-4 rounded-2xl border border-flame/25 bg-flame/8 px-4 py-3 text-sm text-ink">
    <b class="text-navy">Akun siswa dibuat otomatis.</b>
    Saat pendaftar disahkan, sistem membuat akun login untuk siswa tersebut (username memakai NISN).
    Prosesnya dijalankan oleh backend, bukan diisi manual di sini.
</div>

<div class="card">
    <h2 class="sec-title">Menunggu pengesahan</h2>
    @php $menunggu = collect($pendaftar)->where('status', 'menunggu'); @endphp
    @forelse ($menunggu as $r)
        {{-- TODO backend: isi action dengan route pengesahan kelulusan (POST) lalu hapus onsubmit. Field: id_pendaftaran, nilai_ujian, status --}}
        <form method="POST" action="#" onsubmit="return false" data-validasi
              data-konfirmasi="Sahkan {{ $r['nama'] }}? Akun siswa akan dibuat otomatis."
              class="flex flex-wrap items-center justify-between gap-3 border-b border-line py-3 last:border-0">
            @csrf
            <input type="hidden" name="id_pendaftaran" value="{{ $r['id'] }}">
            <div class="min-w-0">
                <b class="text-sm">{{ $r['nama'] }}</b>
                <div class="text-xs text-mute">NISN {{ $r['nisn'] }} · berkas diterima {{ $r['tgl_terima'] }}</div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <input class="input !w-28" name="nilai_ujian" value="{{ $r['nilai'] }}" inputmode="decimal" placeholder="Nilai ujian" aria-label="Nilai ujian {{ $r['nama'] }}" data-wajib>
                <select class="input !w-36" name="status" aria-label="Hasil seleksi {{ $r['nama'] }}">
                    <option value="lulus">Lulus</option>
                    <option value="tidak_lulus">Tidak lulus</option>
                </select>
                <button class="btn btn-flame !py-1.5">Sahkan</button>
            </div>
        </form>
    @empty
        <p class="py-4 text-sm text-mute">Tidak ada pendaftar yang menunggu pengesahan.</p>
    @endforelse
</div>

<div class="card mt-4">
    <h2 class="sec-title">Sudah disahkan</h2>
    @forelse (collect($pendaftar)->where('status', 'disahkan') as $r)
        <div class="flex items-center justify-between gap-3 border-b border-line py-3 text-sm last:border-0">
            <div><b>{{ $r['nama'] }}</b> <span class="text-mute">· NISN {{ $r['nisn'] }} · nilai ujian {{ $r['nilai'] }}</span></div>
            <x-badge status="disahkan" />
        </div>
    @empty
        <p class="py-4 text-sm text-mute">Belum ada.</p>
    @endforelse
</div>
@endsection