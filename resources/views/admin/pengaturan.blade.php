@extends('layouts.app')
@section('title', 'Pengaturan Sistem')
@section('heading', 'Pengaturan Sistem')
@section('sub', 'Pengaturan umum aplikasi. Setiap perubahan tercatat di jejak audit.')
@section('content')
<form class="card max-w-2xl space-y-4" onsubmit="return false">
    @foreach ($set as [$label, $kunci, $nilai, $ket])
        <div>
            <label class="label" for="{{ $kunci }}">{{ $label }}</label>
            <input class="input" id="{{ $kunci }}" name="{{ $kunci }}" value="{{ $nilai }}">
            <p class="text-[11px] text-mute mt-1">{{ $ket }}</p>
        </div>
    @endforeach
    <button class="btn btn-flame">Simpan pengaturan</button>
</form>
@endsection