
@extends('layouts.app')
@section('title', 'Jejak Audit')
@section('heading', 'Jejak Audit')
@section('sub', 'Ringkasan aktivitas pengguna dengan bahasa sederhana.')

@section('content')
<form method="GET" class="card mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 items-end">
    <div>
        <label class="label">Pengubah</label>
        <input class="input" name="cari" value="{{ $f['cari'] ?? '' }}" placeholder="Username">
    </div>

    <div>
        <label class="label">Tabel</label>
        <select class="input" name="tabel">
            <option value="">Semua</option>
            @foreach ($daftarTabel as $nilai => $item)
                <option value="{{ $nilai }}" @selected(($f['tabel'] ?? '') === $nilai)>{{ $item['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label">Aksi</label>
        <select class="input" name="aksi">
            <option value="">Semua</option>
            @foreach (['INSERT' => 'Menambahkan', 'UPDATE' => 'Memperbarui', 'DELETE' => 'Menghapus'] as $a => $label)
                <option value="{{ $a }}" @selected(($f['aksi'] ?? '') === $a)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label">Dari tanggal</label>
        <input class="input" type="date" name="dari" value="{{ $f['dari'] ?? '' }}">
    </div>

    <div>
        <label class="label">Sampai tanggal</label>
        <input class="input" type="date" name="sampai" value="{{ $f['sampai'] ?? '' }}">
    </div>

    <div class="flex gap-2">
        <button class="btn btn-flame flex-1 whitespace-nowrap">Terapkan</button>
        <a href="{{ route('admin.audit') }}" class="btn btn-ghost flex-1 whitespace-nowrap">Reset</a>
    </div>
</form>

<div class="space-y-3">
    @forelse ($log as $l)
        <div class="card">
            <div class="flex items-center justify-between gap-3 flex-wrap text-sm">
                <div>
                    <b>{{ $l['username'] }}</b>
                    <span class="text-mute">· {{ $l['waktu'] }}</span>
                </div>
                <span class="text-sm font-medium text-navy">{{ $l['aktivitas'] }}</span>
            </div>
        </div>
    @empty
        <div class="card text-center text-mute">
            Belum ada catatan audit yang cocok.
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $log->links() }}</div>
@endsection
