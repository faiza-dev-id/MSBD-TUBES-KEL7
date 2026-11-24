@extends('layouts.app')
@section('title', 'Rapor Siswa')
@section('heading', 'Rapor Siswa')
@section('sub', 'Kelola nilai siswa pada kelas yang kamu walikan untuk semester aktif.')
@section('content')
@if (! $kelasWali)
    <div class="card text-sm text-mute">
        Kamu belum ditetapkan sebagai wali kelas pada semester aktif. Hubungi Tata Usaha untuk penugasan wali kelas.
    </div>
@elseif (! $siswa)
    <div class="card text-sm text-mute">Belum ada siswa aktif di kelas ini.</div>
@elseif (! $mapel)
    <div class="card text-sm text-mute">Belum ada mata pelajaran aktif untuk semester ini.</div>
@else
    <form method="GET" action="{{ route('guru.rapor') }}" class="card mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="label" for="pilih-siswa">Siswa</label>
            <select class="input" id="pilih-siswa" name="siswa">
                @foreach ($siswa as $item)
                    <option value="{{ $item['id'] }}" @selected($item['id'] === ($terpilih['id'] ?? null))>
                        {{ $item['nama'] }} · {{ $item['nisn'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="pilih-mapel">Mata pelajaran</label>
            <select class="input" id="pilih-mapel" name="mapel">
                @foreach ($mapel as $item)
                    <option value="{{ $item['id'] }}" @selected($item['id'] === $idMapel)>{{ $item['nama'] }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-flame">Tampilkan</button>
    </form>

    @if (! $terpilih || ! $mapelTerpilih)
        <div class="card text-sm text-mute">Pilih siswa dan mata pelajaran untuk mengelola rapor.</div>
    @else
        @php
            $idSiswaTerpilih = $terpilih['id'];
            $nilaiSaatIni = $nilaiMentah[$idSiswaTerpilih] ?? [];
            $statusRapor = $statusSiswa[$idSiswaTerpilih] ?? 'draft';
            $nilaiAkhirSaatIni = $nilaiTampil[$idSiswaTerpilih] ?? null;
        @endphp

        <section class="card mb-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="sec-title !mb-1">{{ $terpilih['nama'] }}</h2>
                    <p class="text-sm text-mute">{{ $kelas['nama'] }} · {{ $mapelTerpilih['nama'] }}</p>
                </div>
                <div class="text-sm">
                    <span class="text-mute">Status rapor:</span>
                    <span class="font-semibold {{ $statusRapor === 'final' ? 'text-ok' : 'text-warn' }}">
                        {{ $statusRapor === 'final' ? 'Final' : 'Draf' }}
                    </span>
                </div>
            </div>
            <dl class="mt-4 grid gap-3 sm:grid-cols-4">
                @foreach (['tugas' => 'Tugas', 'keterampilan' => 'Keterampilan', 'ujian' => 'Ujian'] as $jenis => $label)
                    <div class="rounded-xl bg-paper p-3">
                        <dt class="text-xs text-mute">{{ $label }}</dt>
                        <dd class="font-semibold">{{ $nilaiSaatIni[$jenis] ?? '—' }}</dd>
                    </div>
                @endforeach
                <div class="rounded-xl bg-tint p-3">
                    <dt class="text-xs text-mute">Nilai akhir</dt>
                    <dd class="font-semibold text-navy">{{ $nilaiAkhirSaatIni ?? '—' }}</dd>
                </div>
            </dl>
        </section>

        @if ($statusRapor !== 'final')
            <form class="card space-y-4" method="POST" action="{{ route('guru.rapor.simpan') }}">
                @csrf
                <input type="hidden" name="id_kelas" value="{{ $kelas['id'] }}">
                <input type="hidden" name="id_mapel" value="{{ $idMapel }}">

                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach (['tugas' => 'Tugas', 'keterampilan' => 'Keterampilan', 'ujian' => 'Ujian'] as $jenis => $label)
                        <div>
                            <label class="label" for="nilai-{{ $jenis }}">{{ $label }}</label>
                            <input class="input" type="number" id="nilai-{{ $jenis }}" min="1" max="100" step="0.01"
                                   name="nilai_mentah[{{ $idSiswaTerpilih }}][{{ $jenis }}]"
                                   value="{{ old("nilai_mentah.$idSiswaTerpilih.$jenis", $nilaiSaatIni[$jenis] ?? '') }}">
                        </div>
                    @endforeach
                </div>
                <div>
                    <label class="label" for="catatan-siswa">Catatan wali kelas</label>
                    <textarea class="input" id="catatan-siswa" name="catatan_siswa[{{ $idSiswaTerpilih }}]" rows="3" maxlength="2000">{{ old("catatan_siswa.$idSiswaTerpilih", $catatanSiswa[$idSiswaTerpilih] ?? '') }}</textarea>
                </div>
                <button class="btn btn-flame">Simpan nilai</button>
            </form>

            @if ($rapor)
                <form class="mt-4" method="POST" action="{{ route('guru.rapor.finalisasi') }}"
                      data-konfirmasi="Rapor ini akan dikunci dan nilai tidak dapat diubah. Lanjutkan?">
                    @csrf
                    <input type="hidden" name="id_rapor" value="{{ $rapor->id_rapor }}">
                    <button class="btn bg-ok hover:bg-ok/90">Kunci rapor</button>
                </form>
            @endif
        @else
            <div class="card border-ok/30 bg-ok/5 text-sm text-ok" role="status">
                Rapor sudah dikunci. Perubahan nilai ditolak.
            </div>
        @endif
    @endif
@endif
@endsection
