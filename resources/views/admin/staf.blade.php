@extends('layouts.app')
@section('title', 'Guru & Staf')
@section('heading', 'Data Guru dan Staf')
@section('sub', 'Data pegawai sekolah. Akun login dibuat dari menu Pengguna.')
@section('content')
<div class="space-y-4">
    <x-daftar :judul="$guru['judul']" :kolom="$guru['kolom']" :baris="$guru['baris']" tambah="Tambah guru" />
    <x-daftar :judul="$staf['judul']" :kolom="$staf['kolom']" :baris="$staf['baris']" tambah="Tambah staf" />
</div>
@endsection