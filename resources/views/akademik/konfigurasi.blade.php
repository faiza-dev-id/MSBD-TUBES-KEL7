@extends('layouts.app')
@section('title', 'Konfigurasi Akademik')
@section('heading', 'Konfigurasi Akademik')
@section('sub', 'Tahun ajaran, semester, kelas, mata pelajaran, dan aturan penilaian.')
@section('content')
<div class="flex gap-1.5 flex-wrap mb-4 text-sm">
    @foreach ($semua as $k => $d)
        <a href="{{ route('akademik.konfigurasi', $k) }}" class="px-3.5 py-1.5 rounded-full border {{ $k === $tab ? 'bg-navy text-paper border-navy' : 'border-line text-navy hover:bg-card' }}">{{ $d['judul'] }}</a>
    @endforeach
</div>
<x-daftar :judul="$semua[$tab]['judul']" :kolom="$semua[$tab]['kolom']" :baris="$semua[$tab]['baris']" tambah="Tambah" />
@if ($tab === 'komponen')
    <p class="text-xs text-mute mt-2">Total bobot semua komponen harus 100%.</p>
@endif
@endsection