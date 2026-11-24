@extends('layouts.app')
@section('title', 'Rapor Siswa')
@section('heading', 'Rapor Siswa')
@section('sub', 'Lihat nilai yang sudah dibagikan oleh Wali Kelas.')
@section('content')
@if ($raporList->isEmpty())
    <div class="card text-center">
        <h2 class="text-lg font-semibold">Belum ada rapor tersedia</h2>
        <p class="mt-2 text-sm text-mute">Rapor akan tampil di sini setelah diterbitkan oleh Wali Kelas.</p>
    </div>
@else
    @if ($raporList->count() > 1)
        <nav class="mb-4 flex flex-wrap gap-2" aria-label="Pilih semester rapor">
            @foreach ($raporList as $item)
                <a href="{{ route('siswa.rapor', ['id' => $item->id_rapor]) }}"
                   @if ($rapor && (int) $rapor->id_rapor === (int) $item->id_rapor) aria-current="page" @endif
                   class="rounded-full border px-4 py-2 text-sm font-medium transition
                       {{ $rapor && (int) $rapor->id_rapor === (int) $item->id_rapor ? 'border-flame bg-flame text-white' : 'border-line bg-white text-ink hover:bg-tint' }}">
                    {{ $item->nama_semester }} · {{ $item->nama_tahun_ajaran }}
                </a>
            @endforeach
        </nav>
    @endif

    @if ($rapor)
        <section class="card mb-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">{{ $rapor->nama_semester }} · {{ $rapor->nama_tahun_ajaran }}</h2>
                    <p class="mt-1 text-sm text-mute">Kelas {{ $rapor->nama_kelas ?? '—' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-tint px-4 py-3 text-center">
                        <div class="text-xs text-mute">Rata-rata</div>
                        <div class="text-lg font-bold text-navy">{{ $rataRata !== null ? number_format($rataRata, 2, ',', '.') : '—' }}</div>
                    </div>
                    <div class="rounded-xl bg-paper px-4 py-3 text-center">
                        <div class="text-xs text-mute">Predikat</div>
                        <div class="text-lg font-bold text-navy">{{ $predikatRataRata ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card mb-4">
            <h2 class="sec-title">Nilai mata pelajaran</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[420px] text-sm">
                    <thead>
                        <tr class="border-b-2 border-line text-left text-xs text-mute">
                            <th class="p-2">Mata pelajaran</th>
                            <th class="p-2 text-right">Nilai</th>
                            <th class="p-2 text-center">Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($detail as $item)
                            <tr class="border-b border-line last:border-0">
                                <td class="p-2.5 font-medium">{{ $item['mapel'] }}</td>
                                <td class="p-2.5 text-right">{{ $item['nilai'] }}</td>
                                <td class="p-2.5 text-center font-semibold">{{ $item['predikat'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-4 text-center text-mute">Belum ada rincian nilai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card mb-4">
            <h2 class="sec-title">Rekap kehadiran</h2>
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach (['jumlah_sakit' => 'Sakit', 'jumlah_izin' => 'Izin', 'jumlah_alpa' => 'Tanpa keterangan'] as $field => $label)
                    <div class="rounded-xl bg-paper p-3">
                        <dt class="text-xs text-mute">{{ $label }}</dt>
                        <dd class="font-semibold">{{ $rapor->{$field} }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        @if ($rapor->catatan_wali)
            <section class="card">
                <h2 class="sec-title">Catatan Wali Kelas</h2>
                <p class="whitespace-pre-line text-sm">{{ $rapor->catatan_wali }}</p>
            </section>
        @endif
    @endif
@endif
@endsection
