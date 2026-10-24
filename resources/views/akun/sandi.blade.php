@extends('layouts.app')
@section('title', 'Ganti Kata Sandi')
@section('heading', 'Ganti Kata Sandi')
@section('sub', 'Gunakan sandi yang sulit ditebak dan jangan dibagikan ke siapa pun.')
@section('content')
<form class="card max-w-md space-y-4" onsubmit="return false">
    <x-field label="Kata sandi lama" name="sandi_lama" type="password" />
    <x-field label="Kata sandi baru" name="sandi_baru" type="password" hint="Minimal 8 karakter, campuran huruf dan angka" />
    <x-field label="Ulangi kata sandi baru" name="sandi_ulang" type="password" />
    <button class="btn btn-flame">Simpan kata sandi</button>
</form>
@endsection