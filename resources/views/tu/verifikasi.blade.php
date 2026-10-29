@extends('layouts.app')
@section('title', 'Verifikasi Berkas')
@section('heading', 'Verifikasi: ' . $row['nama'])
@section('sub', 'NISN ' . $row['nisn'] . ' · masuk ' . $row['tgl'])
@section('content')
<form class="grid lg:grid-cols-[1.3fr_1fr] gap-4 items-start" method="POST" action="{{ route('tu.verifikasi.simpan') }}">
    @csrf
    <input type="hidden" name="id_pendaftaran" value="{{ $row['id'] ?? '' }}">
    <div class="space-y-4">
        @foreach ($checklist as $dokumen => $items)
            <div class="card">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="font-serif text-lg font-bold">{{ $dokumen }}</h2>
                    <a href="#" class="text-sm text-flame-dark font-semibold">Lihat berkas</a>
                </div>
                @foreach ($items as $item)
                    <label class="flex items-center gap-3 py-2 text-sm border-b border-line last:border-0 cursor-pointer">
                        {{-- value nanti diganti id_item dari ref_checklist_item --}}
                        <input type="checkbox" name="lolos[]" value="{{ $item }}" class="size-4 accent-flame"> {{ $item }}
                    </label>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="card lg:sticky lg:top-4">
        <h2 class="sec-title">Keputusan</h2>
        <div class="space-y-3">
            <div><label class="label" for="id_alasan">Alasan (jika dikembalikan / ditolak)</label>
                {{-- value nanti diganti id_alasan dari ref_alasan_pengembalian --}}
                <select class="input" id="id_alasan" name="id_alasan"><option value="">Pilih alasan</option>@foreach ($alasan as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select></div>
            <div><label class="label" for="catatan">Catatan untuk siswa</label><textarea class="input" id="catatan" name="catatan" rows="3"></textarea></div>
            <button class="btn w-full" name="hasil" value="terima">Terima berkas</button>
            <button class="btn btn-ghost w-full" name="hasil" value="kembalikan">Kembalikan untuk diperbaiki</button>
            <button class="btn btn-ghost w-full !text-bad" name="hasil" value="tolak">Tolak</button>
            <hr class="border-line">
            <button class="btn btn-flame w-full" name="hasil" value="sahkan">Sahkan &amp; buat akun siswa</button>
            <p class="text-[11px] text-mute">Akun siswa dibuat otomatis memakai NISN.</p>
        </div>
    </div>
</form>
@endsection