@extends('layouts.app')
@section('title', 'Antrean Verifikasi')
@section('heading', 'Antrean verifikasi PPDB')
@section('sub', 'Berkas pendaftar baru. Cari nama atau NISN, atau saring berdasarkan status.')
@section('content')
@php
    $hitung = collect($antrean)->countBy('status');
    $filter = ['semua' => 'Semua', 'menunggu' => 'Menunggu', 'dikembalikan' => 'Dikembalikan', 'disahkan' => 'Disahkan'];
@endphp

<div class="card">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1.5" id="filterAntrean">
            @foreach ($filter as $kunci => $label)
                <button type="button" data-filter="{{ $kunci }}"
                        class="cursor-pointer rounded-full border px-3 py-1 text-[12.5px] font-semibold transition {{ $kunci === 'semua' ? 'border-navy bg-navy text-white' : 'border-line text-ink hover:bg-tint' }}">
                    {{ $label }} <span class="opacity-70">{{ $kunci === 'semua' ? count($antrean) : ($hitung[$kunci] ?? 0) }}</span>
                </button>
            @endforeach
        </div>
        <input id="cariAntrean" type="search" class="input !w-56" placeholder="Cari nama / NISN" aria-label="Cari nama atau NISN">
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead><tr class="border-b-2 border-line text-left text-xs text-mute"><th class="p-2">Nama</th><th class="p-2">NISN</th><th class="p-2">Jenis</th><th class="p-2">Masuk</th><th class="p-2">Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($antrean as $r)
                <tr class="border-b border-line transition hover:bg-tint/60" data-status="{{ $r['status'] }}" data-cari="{{ mb_strtolower($r['nama'] . ' ' . $r['nisn']) }}">
                    <td class="p-2.5 font-medium">{{ $r['nama'] }}</td>
                    <td class="p-2.5">{{ $r['nisn'] }}</td>
                    <td class="p-2.5">{{ $r['jenis'] }}</td>
                    <td class="p-2.5">{{ $r['tgl'] }}</td>
                    <td class="p-2.5"><x-badge :status="$r['status']" /></td>
                    <td class="p-2.5 text-right"><a class="btn btn-ghost !py-1.5" href="{{ route('tu.verifikasi', $r['id']) }}">Periksa</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p id="kosongAntrean" class="hidden py-6 text-center text-sm text-mute">Tidak ada berkas yang cocok.</p>
</div>

<script>
(function () {
    var baris = document.querySelectorAll('tr[data-status]');
    var kotak = document.getElementById('cariAntrean');
    var tombol = document.querySelectorAll('#filterAntrean [data-filter]');
    var kosong = document.getElementById('kosongAntrean');
    var aktif = 'semua';
    var cls = { on: ['border-navy', 'bg-navy', 'text-white'], off: ['border-line', 'text-ink', 'hover:bg-tint'] };

    function terapkan() {
        var q = kotak.value.trim().toLowerCase(), tampil = 0;
        baris.forEach(function (tr) {
            var cocok = (aktif === 'semua' || tr.dataset.status === aktif) && tr.dataset.cari.indexOf(q) !== -1;
            tr.classList.toggle('hidden', !cocok);
            if (cocok) tampil++;
        });
        kosong.classList.toggle('hidden', tampil > 0);
    }
    tombol.forEach(function (b) {
        b.addEventListener('click', function () {
            aktif = b.dataset.filter;
            tombol.forEach(function (x) {
                var on = x === b;
                cls.on.forEach(function (c) { x.classList.toggle(c, on); });
                cls.off.forEach(function (c) { x.classList.toggle(c, !on); });
            });
            terapkan();
        });
    });
    kotak.addEventListener('input', terapkan);
})();
</script>
@endsection