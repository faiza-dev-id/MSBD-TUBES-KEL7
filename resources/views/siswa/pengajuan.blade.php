@extends('layouts.app')
@section('title', 'Ajukan Perubahan Data')
@section('heading', 'Ajukan Perubahan Data')
@section('sub', 'Pilih data yang berubah dan lampirkan bukti. Perubahan baru berlaku setelah disetujui Tata Usaha.')
@section('content')
@if (! $periode['dibuka'])
    <div class="mb-4 rounded-xl border border-warn/40 bg-warn/10 px-4 py-3 text-sm text-warn">Periode update data sedang ditutup. Kamu belum bisa mengirim pengajuan baru, tapi status pengajuan sebelumnya tetap bisa dilihat.</div>
@endif
<div class="grid lg:grid-cols-[1.2fr_1fr] gap-4 items-start">
    <form class="card space-y-4 {{ $periode['dibuka'] ? '' : 'opacity-50' }}" method="POST" action="{{ route('siswa.pengajuan.simpan') }}" enctype="multipart/form-data" @if (! $periode['dibuka']) inert @endif>
        @csrf
        <h2 class="sec-title">Form pengajuan</h2>
        <div><label class="label" for="id_field">Data yang diubah</label>
            {{-- value nanti diganti id_field dari ref_field_data (yang boleh_diubah_siswa) --}}
            <select class="input" id="id_field" name="id_field"><option value="">Pilih data</option><option value="alamat">Alamat</option><option value="no_hp">No. HP</option><option value="nama">Nama (perlu akta)</option><option value="nik_kk">NIK / No. KK (perlu KK baru)</option></select></div>
        <div><label class="label">Nilai sekarang</label><input class="input opacity-60" value="{{ $s['alamat'] }}" disabled></div>
        <x-field label="Nilai baru" name="nilai_baru" />
        <label class="flex items-center justify-between gap-3 border-2 border-dashed border-line rounded-lg p-3.5 text-sm cursor-pointer hover:border-flame">
            <span>Bukti pendukung <span class="text-mute">(wajib untuk nama, NIK, KK)</span></span><span class="btn btn-ghost !py-1.5">Pilih file</span><input type="file" name="bukti" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
        </label>
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
@endsection