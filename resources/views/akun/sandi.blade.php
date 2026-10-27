
@extends('layouts.app')

@section('title', 'Ganti Kata Sandi')
@section('heading', 'Ganti Kata Sandi')
@section('sub', 'Masukkan kata sandi lama, lalu tentukan kata sandi baru.')

@section('content')
<form
    class="card max-w-md space-y-4"
    method="POST"
    action="{{ route('akun.sandi.update') }}"
    data-validasi
>
    @csrf

    <x-field
        label="Kata sandi lama"
        name="sandi_lama"
        type="password"
        wajib="1"
        autocomplete="current-password"
    />

    <x-field
        label="Kata sandi baru"
        name="sandi_baru"
        type="password"
        wajib="1"
        sandi="1"
        beda="#sandi_lama"
        pesan="Sandi baru tidak boleh sama dengan sandi lama."
        autocomplete="new-password"
        hint="Minimal 8 karakter, mengandung huruf dan angka."
    />

    <x-field
        label="Ulangi kata sandi baru"
        name="sandi_ulang"
        type="password"
        wajib="1"
        cocok="#sandi_baru"
        pesan="Kata sandi ulang tidak sama."
        autocomplete="new-password"
    />

    <button type="submit" class="btn btn-flame">
        Simpan kata sandi
    </button>
</form>
@endsection
