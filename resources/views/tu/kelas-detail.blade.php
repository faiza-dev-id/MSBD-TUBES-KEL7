@extends('layouts.app')
@section('title', 'Siswa per Kelas')
@section('heading', 'Kelas ' . $kelas->nama_kelas)
@section('sub', 'Wali kelas: ' . ($wali ?? '(belum ditetapkan)') . ' · ' . $siswa->count() . ' siswa' . ($cari !== '' ? ' (hasil pencarian)' : ''))
@section('content')
@if (!$sem)
    <div class="card text-sm text-mute">Belum ada semester.</div>
@else
<div class="card">
    <form method="GET" class="flex items-end justify-between gap-3 flex-wrap mb-3">
        <div class="flex items-end gap-3 flex-wrap">
            <div><label class="label" for="semester">Semester</label>
                <select class="input" id="semester" name="semester" onchange="this.form.submit()">
                    @foreach ($semesters as $s)
                        <option value="{{ $s->id_semester }}" @selected($s->id_semester === $sem->id_semester)>{{ $s->nama_semester }} {{ $s->nama_tahun_ajaran }}{{ $s->is_aktif ? ' (aktif)' : '' }}</option>
                    @endforeach
                </select></div>
            <input class="input !w-56" name="cari" value="{{ $cari }}" placeholder="Cari nama / NISN">
            <button class="btn btn-flame">Cari</button>
        </div>
        <a class="btn btn-ghost" href="{{ route('tu.kelas.unduh', ['id' => $kelas->id_kelas, 'semester' => $sem->id_semester]) }}">Unduh daftar (CSV)</a>
    </form>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2 w-12">No</th><th class="p-2">Nama</th><th class="p-2">NIS</th><th class="p-2">NISN</th></tr></thead>
        <tbody>
        @forelse ($siswa as $i => $s)
            <tr class="border-b border-line"><td class="p-2.5 text-mute">{{ $i + 1 }}</td><td class="p-2.5 font-medium">{{ $s->nama_lengkap }}</td><td class="p-2.5">{{ $s->nis ?: '-' }}</td><td class="p-2.5">{{ $s->nisn ?: '-' }}</td></tr>
        @empty
            <tr><td colspan="4" class="p-6 text-center text-mute">Belum ada siswa di kelas ini pada semester ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endif
<a href="{{ route('tu.kelas', $sem ? ['semester' => $sem->id_semester] : []) }}" class="inline-block mt-4 text-sm text-flame-dark font-semibold">← Kembali ke daftar kelas</a>
@endsection
