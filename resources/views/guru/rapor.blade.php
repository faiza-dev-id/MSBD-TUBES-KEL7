@extends('layouts.app')
@section('title', 'Rapor Siswa')
@section('heading', 'Rapor Siswa')
@section('sub', 'Isi nilai rapor seluruh siswa di kelas yang kamu walikan untuk satu mata pelajaran sekaligus.')
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
    <form method="GET" action="{{ route('guru.rapor') }}" class="card mb-4 flex flex-wrap items-end gap-3" data-auto-filter>
        <div>
            <label class="label" for="pilih-mapel">Mata pelajaran</label>
            <select class="input !w-72" id="pilih-mapel" name="mapel" data-searchable>
                @foreach ($mapel as $item)
                    <option value="{{ $item['id'] }}" @selected((int) $item['id'] === (int) $idMapel)>{{ $item['nama'] }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('guru.rapor') }}" class="btn btn-ghost">Reset</a>
    </form>

    @if (! $mapelTerpilih)
        <div class="card text-sm text-mute">Pilih mata pelajaran untuk menampilkan nilai rapor.</div>
    @else
        <section class="card p-0 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-4">
                <div>
                    <h2 class="sec-title !mb-1">Nilai rapor · {{ $mapelTerpilih['nama'] }}</h2>
                    <p class="text-sm text-mute">
                        {{ $kelas['nama'] }} · {{ $semester->nama_semester }} · {{ count($siswa) }} siswa
                    </p>
                </div>
                <p class="text-xs text-mute">
                    Bobot: Tugas {{ (float) ($bobotKomponen['tugas'] ?? 0) }}% ·
                    Keterampilan {{ (float) ($bobotKomponen['keterampilan'] ?? 0) }}% ·
                    Ujian {{ (float) ($bobotKomponen['ujian'] ?? 0) }}%
                </p>
            </div>

            <form
                id="form-simpan-nilai"
                class="space-y-0"
                method="POST"
                action="{{ route('guru.rapor.simpan') }}"
                x-data='{
                    bobot: @json($bobotKomponen),
                    nilaiAkhir(baris, bobot, fallback) {
                        let jumlah = 0;
                        let totalBobot = 0;

                        for (const jenis of ["tugas", "keterampilan", "ujian"]) {
                            const teks = baris[jenis];
                            if (teks === null || typeof teks === "undefined" || String(teks).trim() === "") {
                                continue;
                            }

                            const angka = Number(teks);
                            if (!Number.isFinite(angka)) {
                                continue;
                            }

                            const bobotKomponen = Number(bobot[jenis] || 0);
                            if (bobotKomponen <= 0) {
                                continue;
                            }

                            jumlah += angka * bobotKomponen;
                            totalBobot += bobotKomponen;
                        }

                        if (totalBobot <= 0) {
                            return fallback === null || typeof fallback === "undefined" || fallback === ""
                                ? null
                                : Number(fallback).toFixed(2);
                        }

                        return (Math.round((jumlah / totalBobot) * 100) / 100).toFixed(2);
                    }
                }'
            >
                @csrf
                <input type="hidden" name="id_kelas" value="{{ $kelas['id'] }}">
                <input type="hidden" name="id_mapel" value="{{ $idMapel }}">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm border-collapse">
                        <thead>
                            <tr class="bg-tint/60 text-xs">
                                <th class="border-b border-line px-3 py-3 text-center font-semibold text-ink w-14">No.</th>
                                <th class="border-b border-line px-3 py-3 text-left font-semibold text-ink min-w-[220px]">Nama siswa</th>
                                <th class="border-b border-line px-3 py-3 text-center font-semibold text-ink w-28">Tugas</th>
                                <th class="border-b border-line px-3 py-3 text-center font-semibold text-ink w-32">Keterampilan</th>
                                <th class="border-b border-line px-3 py-3 text-center font-semibold text-ink w-28">Ujian</th>
                                <th class="border-b border-line px-3 py-3 text-center font-semibold text-ink w-32">Nilai akhir</th>
                                <th class="border-b border-line px-3 py-3 text-center font-semibold text-ink w-24">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($siswa as $index => $s)
                                @php
                                    $id = (int) $s['id'];
                                    $final = ($statusSiswa[$id] ?? 'draft') === 'final';
                                    $nilaiAwal = $nilaiMentah[$id] ?? ['tugas' => null, 'keterampilan' => null, 'ujian' => null];
                                @endphp
                                <tr
                                    class="hover:bg-tint/10 transition-colors"
                                    x-data='{
                                        tugas: @json(old('nilai.'.$id.'.tugas', $nilaiAwal['tugas'] ?? null)),
                                        keterampilan: @json(old('nilai.'.$id.'.keterampilan', $nilaiAwal['keterampilan'] ?? null)),
                                        ujian: @json(old('nilai.'.$id.'.ujian', $nilaiAwal['ujian'] ?? null)),
                                        fallback: @json($nilaiTampil[$id] ?? null)
                                    }'
                                >
                                    <td class="px-3 py-3 text-center align-middle text-mute">{{ $index + 1 }}</td>
                                    <td class="px-3 py-3 align-middle">
                                        <div class="font-semibold text-ink leading-tight">{{ $s['nama'] }}</div>
                                        <div class="text-[11px] text-mute mt-0.5">NISN {{ $s['nisn'] }}</div>
                                    </td>
                                    @foreach (['tugas' => 'Tugas', 'keterampilan' => 'Keterampilan', 'ujian' => 'Ujian'] as $jenis => $label)
                                        <td class="px-3 py-3 text-center align-middle">
                                            <input
                                                class="input !py-1.5 !px-2 !text-xs w-full text-center"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                aria-label="{{ $label }} {{ $s['nama'] }}"
                                                name="nilai[{{ $id }}][{{ $jenis }}]"
                                                value="{{ old('nilai.'.$id.'.'.$jenis, $nilaiAwal[$jenis] ?? '') }}"
                                                x-model.number="{{ $jenis }}"
                                                @disabled($final)
                                            >
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-3 text-center align-middle">
                                        <span
                                            class="inline-flex min-w-[4rem] justify-center rounded-lg bg-tint px-2 py-1 font-semibold text-navy"
                                            x-text="nilaiAkhir({ tugas, keterampilan, ujian }, bobot, fallback) || '—'"
                                        >{{ $nilaiTampil[$id] !== null ? number_format((float) $nilaiTampil[$id], 2, '.', '') : '—' }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center align-middle">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $final ? 'bg-ok/10 text-ok' : 'bg-warn/10 text-warn' }}">
                                            {{ $final ? 'Terkunci' : 'Draf' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-4">
                <p class="text-xs text-mute">
                    Nilai akhir dihitung otomatis dari komponen yang diisi. Baris dengan rapor terkunci tidak dapat diubah.
                </p>
                <div class="flex items-center gap-3">
                    <button type="submit" form="form-simpan-nilai" class="btn btn-flame">Simpan nilai</button>
                    @if ($idRaporSiapDikunci)
                        <form
                            class="inline"
                            method="POST"
                            action="{{ route('guru.rapor.finalisasi') }}"
                            data-validasi
                            data-konfirmasi="Kunci {{ count($idRaporSiapDikunci) }} rapor yang sudah terisi? Nilai yang dikunci tidak dapat diubah lagi."
                        >
                            @csrf
                            <input type="hidden" name="id_mapel" value="{{ $idMapel }}">
                            @foreach ($idRaporSiapDikunci as $idRapor)
                                <input type="hidden" name="id_rapor[]" value="{{ $idRapor }}">
                            @endforeach
                            <button class="btn bg-ok hover:bg-ok/90">Kunci rapor</button>
                        </form>
                    @else
                        <button type="button" class="btn !bg-ok/40 !text-white cursor-not-allowed" disabled>Kunci rapor</button>
                    @endif
                </div>
            </div>
        </section>
    @endif
@endif
@endsection
