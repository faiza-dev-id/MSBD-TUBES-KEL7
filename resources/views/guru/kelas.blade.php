@extends('layouts.app')
@section('title', 'Daftar Siswa Kelas')
@section('heading', 'Daftar Siswa ' . (($kelas['nama'] ?? null) ? 'Kelas ' . $kelas['nama'] : 'Kelas'))
@section('sub', 'Kelas yang kamu walikan. Data induk lengkap hanya bisa diubah oleh Tata Usaha.')
@section('content')
@if (!($kelas ?? null))
    <div class="card text-sm text-mute">Kamu tidak menjadi wali kelas pada semester aktif, atau data kelas belum tersedia.</div>
@else
    @if (count($kelasWali ?? []) > 1)
        <form method="GET" class="mb-4 flex items-end gap-2" data-auto-filter>
            <div>
                <label class="label" for="kelas">Kelas</label>
                <select class="input !w-56" id="kelas" name="kelas">
                    @foreach ($kelasWali as $k)
                        <option value="{{ $k['id'] }}" @selected(($kelas['id'] ?? 0) == $k['id'])>{{ $k['nama'] }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('guru.kelas') }}" class="btn btn-ghost">Reset</a>
        </form>
    @endif
    <div class="card">
        <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
            <div class="text-sm font-medium">{{ $kelas['nama'] }}</div>
            <span class="text-sm text-mute">{{ $siswa->total() }} siswa</span>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2 w-12">No</th><th class="p-2">Nama</th><th class="p-2">NISN</th></tr></thead>
            <tbody>
            @forelse ($siswa as $i => $s)
                <tr class="border-b border-line"><td class="p-2.5 text-mute">{{ $siswa->firstItem() + $i }}</td><td class="p-2.5 font-medium">{{ $s['nama'] }}</td><td class="p-2.5">{{ $s['nisn'] }}</td></tr>
            @empty
                <tr><td colspan="3" class="p-4 text-center text-mute">Belum ada siswa aktif di kelas ini.</td></tr>
            @endforelse
            </tbody>
        </table>
        <x-pagination :paginator="$siswa" label="siswa" />
    </div>
@endif
@endsection