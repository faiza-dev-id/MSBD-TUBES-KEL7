@extends('layouts.app')
@section('title', 'Antrean Verifikasi')
@section('heading', 'Antrean verifikasi PPDB')
@section('sub', 'Berkas pendaftar baru. Cari nama atau NISN, atau saring berdasarkan status.')
@section('content')
@php
    $filter = ['semua' => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'dikembalikan' => 'Dikembalikan', 'disahkan' => 'Disahkan'];
@endphp

<div class="card">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1.5">
            @foreach ($filter as $kunci => $label)
                <a href="{{ route('tu.antrean', array_filter(['status' => $kunci, 'cari' => $cari], fn ($nilai) => $nilai !== '')) }}"
                   class="rounded-full border px-3 py-1 text-[12.5px] font-semibold transition {{ $status === $kunci ? 'border-navy bg-navy text-white' : 'border-line text-ink hover:bg-tint' }}"
                   @if ($status === $kunci) aria-current="page" @endif>
                    {{ $label }} <span class="opacity-70">{{ $hitung[$kunci] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('tu.antrean') }}" class="flex w-full items-center gap-2 sm:w-auto" data-auto-filter>
            @if ($status !== 'semua')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <input id="cariAntrean" name="cari" type="search" class="input min-w-0 w-full sm:w-56" value="{{ $cari }}" placeholder="Cari nama / NISN" aria-label="Cari nama atau NISN">
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead><tr class="border-b-2 border-line text-left text-xs text-mute"><th class="p-2">Nama</th><th class="p-2">NISN</th><th class="p-2">Jenis</th><th class="p-2">Masuk</th><th class="p-2">Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($antrean as $r)
                <tr class="border-b border-line transition hover:bg-tint/60">
                    <td class="p-2.5 font-medium">{{ $r['nama'] }}</td>
                    <td class="p-2.5">{{ $r['nisn'] }}</td>
                    <td class="p-2.5">{{ $r['jenis'] }}</td>
                    <td class="p-2.5">{{ $r['tgl'] }}</td>
                    <td class="p-2.5"><x-badge :status="$r['status']" /></td>
                    <td class="p-2.5 text-right"><a class="btn btn-ghost !py-1.5" href="{{ route('tu.verifikasi', $r['id']) }}">Periksa</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-sm text-mute">Tidak ada berkas yang cocok.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($antrean->hasPages())
        <div class="mt-4">{{ $antrean->links() }}</div>
    @endif
</div>
@endsection
