@extends('layouts.app')
@section('title', 'Data Referensi')
@section('heading', 'Data Referensi')
@section('sub', 'Daftar pilihan yang dipakai di formulir, seperti agama, pekerjaan, dan sekolah asal.')
@section('content')
<div class="flex gap-1.5 flex-wrap mb-4 text-sm">
    @foreach ($semua as $k => $d)
        <a href="{{ route('admin.referensi', $k) }}" class="px-3.5 py-1.5 rounded-full border {{ $k === $tab ? 'bg-navy text-paper border-navy' : 'border-line text-navy hover:bg-card' }}">{{ $d['judul'] }}</a>
    @endforeach
</div>
<x-daftar :judul="$semua[$tab]['judul']" :kolom="$semua[$tab]['kolom']" :baris="$semua[$tab]['baris']" tambah="Tambah" />
@endsection