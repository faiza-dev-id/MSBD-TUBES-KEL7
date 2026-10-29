@extends('layouts.app')
@section('title', 'Rapor Siswa')
@section('heading', 'Rapor Siswa')
@section('sub', 'Isi nilai rapor siswa di kelas yang kamu walikan.')
@section('content')
@if (!$kelasWali)
    <div class="card text-sm text-mute">
        Kamu belum ditetapkan sebagai wali kelas pada semester aktif. Hubungi Tata Usaha untuk penugasan wali kelas.
    </div>
@else
    <form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="label" for="kelas">Kelas</label>
            <select class="input" id="kelas" name="kelas" onchange="this.form.submit()">
                @foreach ($kelasWali as $k)
                    <option value="{{ $k['id'] }}" @selected($kelas && $kelas['id'] === $k['id'])>{{ $k['nama'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="siswa">Siswa</label>
            <select class="input" id="siswa" name="siswa" onchange="this.form.submit()">
                @forelse ($siswa as $s)
                    <option value="{{ $s['id'] }}" @selected($terpilih && $terpilih['id'] === $s['id'])>{{ $s['nama'] }} - {{ $s['nisn'] }}</option>
                @empty
                    <option value="">Belum ada siswa</option>
                @endforelse
            </select>
        </div>
        <noscript><button class="btn btn-flame">Tampilkan</button></noscript>
    </form>

    @if (!$terpilih)
        <div class="card text-sm text-mute">Belum ada siswa aktif di kelas ini.</div>
    @elseif (!$mapel)
        <div class="card text-sm text-mute">
            Belum ada mata pelajaran. Tambahkan mata pelajaran di Konfigurasi Akademik atau susun jadwal pelajaran kelas.
        </div>
    @else
        <div class="mb-4 grid gap-3 sm:grid-cols-4">
            <div class="card !p-4">
                <div class="text-xs text-mute">Hadir</div>
                <div class="mt-1 text-2xl font-bold">{{ $rekap['hadir'] }}</div>
            </div>
            <div class="card !p-4">
                <div class="text-xs text-mute">Sakit</div>
                <div class="mt-1 text-2xl font-bold">{{ $rekap['sakit'] }}</div>
            </div>
            <div class="card !p-4">
                <div class="text-xs text-mute">Izin</div>
                <div class="mt-1 text-2xl font-bold">{{ $rekap['izin'] }}</div>
            </div>
            <div class="card !p-4">
                <div class="text-xs text-mute">Alpha</div>
                <div class="mt-1 text-2xl font-bold">{{ $rekap['alpha'] }}</div>
            </div>
        </div>

        <form class="card" method="POST" action="{{ route('guru.rapor.simpan') }}">
            @csrf
            <input type="hidden" name="id_kelas" value="{{ $kelas['id'] }}">
            <input type="hidden" name="id_siswa" value="{{ $terpilih['id'] }}">

            <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
                <div>
                    <h2 class="sec-title !mb-0">{{ $terpilih['nama'] }}</h2>
                    <p class="text-xs text-mute">{{ $kelas['nama'] }} - {{ $semester->nama_semester ?? '-' }}</p>
                </div>
                @if ($rapor)
                    <span class="text-sm text-mute">Rata-rata tersimpan: <b class="text-ink">{{ rtrim(rtrim(number_format((float) $rapor->rata_rata, 2, '.', ''), '0'), '.') ?: '-' }}</b></span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[480px]">
                    <thead>
                        <tr class="text-left text-xs text-mute border-b-2 border-line">
                            <th class="p-2 w-12">No</th>
                            <th class="p-2">Mata pelajaran</th>
                            <th class="p-2 w-32">Nilai akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mapel as $i => $m)
                            <tr class="border-b border-line">
                                <td class="p-2.5 text-mute">{{ $i + 1 }}</td>
                                <td class="p-2.5 font-medium">{{ $m['nama'] }}</td>
                                <td class="p-2.5">
                                    <input class="input !py-1.5" type="number" step="0.01" min="0" max="100" name="nilai[{{ $m['id'] }}]" value="{{ $nilai[$m['id']] ?? '' }}" placeholder="0-100">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <label class="label" for="catatan_wali">Catatan wali kelas</label>
                <textarea class="input" id="catatan_wali" name="catatan_wali" rows="3" maxlength="2000">{{ $rapor->catatan_wali ?? '' }}</textarea>
            </div>

            <div class="mt-3">
                <label class="label" for="status_rapor">Status rapor</label>
                <select class="input" id="status_rapor" name="status_rapor">
                    <option value="draft" @selected(($rapor->status_rapor ?? 'draft') === 'draft')>Draft</option>
                    <option value="final" @selected(($rapor->status_rapor ?? 'draft') === 'final')>Final</option>
                </select>
            </div>

            <p class="text-xs text-mute mt-3">Jumlah sakit, izin, dan alpha dihitung otomatis dari presensi kelas. Kosongkan nilai bila mata pelajaran belum dinilai.</p>
            <button class="btn btn-flame mt-3">Simpan rapor</button>
        </form>
    @endif
@endif
@endsection
