@extends('layouts.app')
@section('title', 'Daftar Kelas')
@section('heading', 'Kelas dan Wali Kelas')
@section('sub', 'Lihat kelas, tetapkan wali kelas, dan buka daftar siswa per kelas. Wali kelas ditetapkan per semester.')
@section('content')
@if (session('success'))
    <div class="card mb-4 text-sm text-ok font-semibold">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="card mb-4 text-sm text-bad">{{ $errors->first() }}</div>
@endif

@if (!$sem)
    <div class="card text-sm text-mute">Belum ada semester. Tambahkan tahun ajaran dan semester di
        <a class="text-flame-dark font-semibold" href="{{ route('akademik.konfigurasi', 'semester') }}">Konfigurasi Akademik</a>.</div>
@else
@php
    // Peringatan: kelas tanpa wali, dan guru yang menjadi wali di beberapa kelas.
    $tanpaWali = $kelas->filter(fn ($k) => $k->id_guru === null)->pluck('nama_kelas');
    $waliGanda = $kelas->filter(fn ($k) => $k->id_guru !== null)->groupBy('nama_guru')->filter(fn ($g) => $g->count() > 1);
@endphp
<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
    <div><label class="label" for="semester">Semester</label>
        <select class="input" id="semester" name="semester" onchange="this.form.submit()">
            @foreach ($semesters as $s)
                <option value="{{ $s->id_semester }}" @selected($s->id_semester === $sem->id_semester)>{{ $s->nama_semester }} {{ $s->nama_tahun_ajaran }}{{ $s->is_aktif ? ' (aktif)' : '' }}</option>
            @endforeach
        </select></div>
    <noscript><button class="btn btn-flame">Tampilkan</button></noscript>
</form>

@if ($tanpaWali->isNotEmpty())
    <div class="mb-4 rounded-2xl border border-sun/40 bg-sun/10 px-4 py-3 text-sm text-sun-dark" role="status">
        Kelas yang belum punya wali: <b>{{ $tanpaWali->join(', ') }}</b>.
    </div>
@endif
@foreach ($waliGanda as $nama => $grup)
    <div class="mb-4 rounded-2xl border border-sun/40 bg-sun/10 px-4 py-3 text-sm text-sun-dark" role="status">
        Periksa lagi: <b>{{ $nama }}</b> tercatat menjadi wali di {{ $grup->pluck('nama_kelas')->join(' dan ') }}.
    </div>
@endforeach

<div class="grid lg:grid-cols-[1.4fr_1fr] gap-4 items-start">
    <div class="card">
        <h2 class="sec-title">Daftar kelas</h2>
        @forelse ($kelas as $k)
            <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0 text-sm flex-wrap">
                <div><b>{{ $k->nama_kelas }}</b>
                    <span class="text-mute">· {{ $k->jumlah }} siswa{{ $k->kapasitas ? ' dari '.$k->kapasitas : '' }}</span>
                    <div class="text-xs {{ $k->id_guru ? 'text-mute' : 'text-bad' }}">Wali: {{ $k->nama_guru ?? '(belum ditetapkan)' }}</div></div>
                <div class="flex gap-2">
                    <a href="{{ route('tu.kelas.detail', ['id' => $k->id_kelas, 'semester' => $sem->id_semester]) }}" class="btn btn-ghost !py-1.5">Daftar siswa</a>
                    @if ($k->id_penugasan_wali_kelas)
                        <form method="POST" action="{{ route('tu.kelas.wali.cabut', $k->id_penugasan_wali_kelas) }}" onsubmit="return confirm('Cabut wali kelas {{ e($k->nama_kelas) }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost !py-1.5 !text-bad">Cabut wali</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-mute py-4">Belum ada kelas untuk tahun ajaran ini. Tambahkan di
                <a class="text-flame-dark font-semibold" href="{{ route('akademik.konfigurasi', 'kelas') }}">Konfigurasi Akademik</a>.</p>
        @endforelse
    </div>
    <form class="card space-y-4" method="POST" action="{{ route('tu.kelas.wali') }}">
        @csrf
        <h2 class="sec-title">Tetapkan wali kelas</h2>
        <input type="hidden" name="id_semester" value="{{ $sem->id_semester }}">
        <p class="text-xs text-mute">Berlaku untuk semester {{ $sem->nama_semester }} {{ $sem->nama_tahun_ajaran }}. Jika kelas sudah punya wali, wali lama diganti.</p>
        <div><label class="label" for="id_kelas">Kelas</label>
            <select class="input" id="id_kelas" name="id_kelas" required>
                @foreach ($kelas as $k)<option value="{{ $k->id_kelas }}" @selected((string) old('id_kelas') === (string) $k->id_kelas)>{{ $k->nama_kelas }}</option>@endforeach
            </select></div>
        <div><label class="label" for="id_guru">Guru</label>
            <select class="input" id="id_guru" name="id_guru" required>
                @foreach ($guru as $g)<option value="{{ $g->id_guru }}" @selected((string) old('id_guru') === (string) $g->id_guru)>{{ $g->nama_guru }}</option>@endforeach
            </select></div>
        <button class="btn btn-flame w-full" @disabled($kelas->isEmpty() || $guru->isEmpty())>Tetapkan</button>
    </form>
</div>
@endif
@endsection
