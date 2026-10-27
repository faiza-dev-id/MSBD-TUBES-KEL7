@extends('layouts.app')
@section('title', 'Pengaturan Sistem')
@section('heading', 'Pengaturan Sistem')
@section('sub', 'Pengaturan umum aplikasi. Setiap perubahan tercatat di jejak audit.')
@section('content')
@if (session('success'))
    <div class="card max-w-2xl mb-4 text-sm text-ok font-semibold">{{ session('success') }}</div>
@endif
<form method="POST" action="{{ route('admin.pengaturan.simpan') }}" class="card max-w-2xl space-y-4">
    @csrf
    @method('PUT')
    @foreach ($set as [$label, $kunci, $nilai, $ket])
        <div>
            <label class="label" for="{{ $kunci }}">{{ $label }}</label>
            @if ($kunci === 'tahun_ajaran_aktif')
                <select class="input" id="{{ $kunci }}" name="{{ $kunci }}">
                    @foreach ($tahunAjaran as $t)
                        <option value="{{ $t }}" @selected(old($kunci, $nilai) === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            @else
                <input class="input" id="{{ $kunci }}" name="{{ $kunci }}" value="{{ old($kunci, $nilai) }}">
            @endif
            <p class="text-[11px] text-mute mt-1">{{ $ket }}</p>
            @error($kunci)
                <p class="text-xs text-bad mt-1">{{ $message }}</p>
            @enderror
        </div>
    @endforeach
    <button class="btn btn-flame">Simpan pengaturan</button>
</form>
@endsection
