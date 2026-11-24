@extends('layouts.app')
@section('title', 'Ajukan Perubahan Data')
@section('heading', 'Ajukan Perubahan Data')
@section('sub', 'Pilih data yang berubah dan lampirkan bukti. Nilai baru akan menggantikan data saat ini setelah disetujui Wali Kelas.')
@section('content')
@unless ($terhubung)
    <div class="card text-sm text-mute" role="status">
        Akun ini belum terhubung ke data siswa. Hubungi Tata Usaha sebelum mengajukan perubahan.
    </div>
@else
@if (! $periode['dibuka'])
    <div class="mb-4 rounded-xl border border-warn/40 bg-warn/10 px-4 py-3 text-sm text-warn">Periode update data sedang ditutup. Kamu belum bisa mengirim pengajuan baru, tapi status pengajuan sebelumnya tetap bisa dilihat.</div>
@endif
<div class="grid lg:grid-cols-[1.2fr_1fr] gap-4 items-start">
    <form class="card space-y-4 {{ $periode['dibuka'] ? '' : 'opacity-50' }}" method="POST" action="{{ route('siswa.pengajuan.simpan') }}" enctype="multipart/form-data" data-validasi data-form-pengajuan @if (! $periode['dibuka']) inert @endif>
        @csrf
        <h2 class="sec-title">Form pengajuan</h2>
        <div><label class="label" for="id_field">Data yang diubah</label>
            <select class="input" id="id_field" name="id_field" data-pengajuan-field>
                <option value="">Pilih data</option>
                @foreach ($fieldDapatDiubah ?? [] as $f)
                    <option value="{{ $f->id_field }}" data-perlu-bukti="{{ $f->perlu_bukti ? '1' : '0' }}" @selected(old('id_field') == $f->id_field)>
                        {{ $f->label_field }}
                    </option>
                @endforeach
            </select></div>
        <div><label class="label">Nilai sekarang</label><input class="input opacity-60" value="Lihat pada profil/data lama pengajuan" disabled></div>
        <x-field label="Nilai baru" name="nilai_baru" />
        <label class="flex items-center justify-between gap-3 border-2 border-dashed border-line rounded-lg p-3.5 text-sm cursor-pointer hover:border-flame">
            <span class="min-w-0">
                <span class="block" data-pengajuan-bukti-label>Bukti pendukung</span>
                <span class="nama-file block text-xs text-mute truncate">Belum ada file</span>
            </span>
            <span class="btn btn-ghost !py-1.5">Pilih file</span>
            <input type="file" name="bukti" class="hidden" accept=".pdf,.jpg,.jpeg,.png" data-nama="Bukti pendukung" data-pengajuan-bukti>
        </label>
        <p class="text-xs text-mute" data-pengajuan-bukti-hint>Pilih data untuk melihat apakah bukti pendukung wajib dilampirkan.</p>
        @error('bukti')
            <p class="text-sm text-warn" role="alert">{{ $message }}</p>
        @enderror
        <button class="btn btn-flame">Kirim pengajuan</button>
    </form>

    <div class="card">
        <h2 class="sec-title">Status pengajuan</h2>
        <ul>
            @foreach ($pengajuan as $p)
                <li class="py-3 border-b border-line last:border-0 text-sm">
                    <div class="flex justify-between gap-2"><b>{{ $p['field'] }}</b><x-badge :status="$p['status']" /></div>
                    <div class="text-mute mt-1">{{ $p['lama'] }} → {{ $p['baru'] }}</div>
                    <div class="text-xs text-mute">{{ $p['tgl'] }}</div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endunless
@endsection