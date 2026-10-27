@extends('layouts.app')
@section('title', 'Log Login')
@section('heading', 'Log Login')
@section('sub', 'Riwayat percobaan masuk, berhasil maupun gagal. Gagal beruntun akan mengunci akun sementara.')
@section('content')
<form method="GET" class="card mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
    <div><label class="label">Username</label>
        <input class="input" name="cari" value="{{ $f['cari'] ?? '' }}" placeholder="Cari username"></div>
    <div><label class="label">Hasil</label>
        <select class="input" name="hasil">
            <option value="">Semua</option>
            <option value="berhasil" @selected(($f['hasil'] ?? '') === 'berhasil')>Berhasil</option>
            <option value="gagal" @selected(($f['hasil'] ?? '') === 'gagal')>Gagal</option>
        </select></div>
    <div><label class="label">Dari tanggal</label>
        <input class="input" type="date" name="dari" value="{{ $f['dari'] ?? '' }}"></div>
    <div><label class="label">Sampai tanggal</label>
        <input class="input" type="date" name="sampai" value="{{ $f['sampai'] ?? '' }}"></div>
    <div class="flex gap-2">
        <button class="btn btn-flame">Terapkan</button>
        <a href="{{ route('admin.log') }}" class="btn btn-ghost">Reset</a>
    </div>
</form>
@if ($errors->any())
    <div class="card mb-4 text-sm text-bad">{{ $errors->first() }}</div>
@endif
<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Waktu</th><th class="p-2">Username</th><th class="p-2">Hasil</th><th class="p-2">IP</th><th class="p-2">Perangkat</th></tr></thead>
            <tbody>
            @forelse ($log as $l)
                <tr class="border-b border-line">
                    <td class="p-2.5">{{ $l['waktu'] }}</td><td class="p-2.5 font-medium">{{ $l['user'] }}</td>
                    <td class="p-2.5"><span class="text-xs font-semibold {{ $l['ok'] ? 'text-ok' : 'text-bad' }}">{{ $l['ok'] ? 'Berhasil' : 'Gagal' }}</span></td>
                    <td class="p-2.5 text-mute">{{ $l['ip'] }}</td><td class="p-2.5 text-mute">{{ $l['perangkat'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-mute">Belum ada catatan login yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $log->links() }}</div>
</div>
@endsection
