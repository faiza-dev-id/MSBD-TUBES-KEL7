@extends('layouts.app')
@section('title', 'Jejak Audit')
@section('heading', 'Jejak Audit')
@section('sub', 'Setiap perubahan data tercatat otomatis: siapa, kapan, dan nilai sebelum dan sesudahnya.')
@section('content')
<form method="GET" class="card mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end">
    <div><label class="label">Pengubah</label>
        <input class="input" name="cari" value="{{ $f['cari'] ?? '' }}" placeholder="Username"></div>
    <div><label class="label">Tabel</label>
        <select class="input" name="tabel">
            <option value="">Semua</option>
            @foreach ($daftarTabel as $t)
                <option value="{{ $t }}" @selected(($f['tabel'] ?? '') === $t)>{{ $t }}</option>
            @endforeach
        </select></div>
    <div><label class="label">Aksi</label>
        <select class="input" name="aksi">
            <option value="">Semua</option>
            @foreach (['INSERT', 'UPDATE', 'DELETE'] as $a)
                <option value="{{ $a }}" @selected(($f['aksi'] ?? '') === $a)>{{ $a }}</option>
            @endforeach
        </select></div>
    <div><label class="label">Dari tanggal</label>
        <input class="input" type="date" name="dari" value="{{ $f['dari'] ?? '' }}"></div>
    <div><label class="label">Sampai tanggal</label>
        <input class="input" type="date" name="sampai" value="{{ $f['sampai'] ?? '' }}"></div>
    <div class="flex gap-2">
        <button class="btn btn-flame">Terapkan</button>
        <a href="{{ route('admin.audit') }}" class="btn btn-ghost">Reset</a>
    </div>
</form>
@if ($errors->any())
    <div class="card mb-4 text-sm text-bad">{{ $errors->first() }}</div>
@endif
<div class="space-y-3">
    @forelse ($log as $l)
        <div class="card">
            <div class="flex items-center justify-between gap-3 flex-wrap text-sm">
                <div><b>{{ $l['user'] }}</b> <span class="text-mute">· {{ $l['waktu'] }}</span></div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-navy/10 text-navy">{{ $l['aksi'] }} · {{ $l['tabel'] }} {{ $l['rec'] }}</span>
            </div>
            <div class="grid sm:grid-cols-2 gap-3 mt-3 text-sm">
                <div class="bg-bad/10 rounded-md p-3 break-words"><div class="text-xs text-mute mb-1">Sebelum</div>{{ $l['lama'] }}</div>
                <div class="bg-ok/10 rounded-md p-3 break-words"><div class="text-xs text-mute mb-1">Sesudah</div>{{ $l['baru'] }}</div>
            </div>
        </div>
    @empty
        <div class="card text-center text-mute">Belum ada catatan audit yang cocok.</div>
    @endforelse
</div>
<div class="mt-4">{{ $log->links() }}</div>
@endsection
