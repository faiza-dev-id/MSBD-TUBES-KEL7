<div>
    <!-- Because you are alive, everything is possible. - Thich Nhat Hanh -->
</div>
@props([
    'jadwal' => [],
    'hari' => [],
    'detailKey' => 'guru',
    'detailLabel' => 'Guru Pengajar',
    'idPrefix' => 'jadwal',
])

@php
    $singkatanHari = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum'];
    $awalMinggu = now('Asia/Jakarta')->startOfWeek(\Carbon\Carbon::MONDAY);
    $hariIni = (int) now('Asia/Jakarta')->dayOfWeekIso;
    $hariFallback = array_key_exists(5, $hari) ? 5 : (int) array_key_first($hari);
    $hariTerpilih = array_key_exists($hariIni, $hari) ? $hariIni : $hariFallback;
@endphp

<div class="space-y-4" data-jadwal-picker data-default-day="{{ $hariTerpilih }}">
    <div class="-mx-1 overflow-x-auto px-1 pb-1">
        <div class="flex min-w-max gap-2" role="tablist" aria-label="Pilih hari jadwal">
            @foreach ($hari as $nomorHari => $namaHari)
                @php
                    $aktif = (int) $nomorHari === $hariTerpilih;
                    $tanggalHari = $awalMinggu->copy()->addDays((int) $nomorHari - 1);
                @endphp
                <button
                    class="flex min-w-[4.75rem] flex-col items-center gap-1 rounded-xl border px-3 py-2 transition {{ $aktif ? 'border-flame bg-flame text-white shadow-sm' : 'border-line bg-white text-ink hover:bg-tint' }}"
                    type="button"
                    id="{{ $idPrefix }}-hari-tab-{{ $nomorHari }}"
                    role="tab"
                    aria-controls="{{ $idPrefix }}-hari-panel-{{ $nomorHari }}"
                    aria-selected="{{ $aktif ? 'true' : 'false' }}"
                    tabindex="{{ $aktif ? '0' : '-1' }}"
                    data-hari-tab="{{ $nomorHari }}"
                >
                    <span class="text-xs font-bold uppercase">{{ $singkatanHari[$nomorHari] ?? $namaHari }}</span>
                    <time
                        class="text-[11px] {{ $aktif ? 'text-white/80' : 'text-mute' }}"
                        data-hari-tanggal
                        datetime="{{ $tanggalHari->format('Y-m-d') }}"
                    >{{ $tanggalHari->locale('id')->translatedFormat('d M') }}</time>
                </button>
            @endforeach
        </div>
    </div>

    @foreach ($hari as $nomorHari => $namaHari)
        @php
            $aktif = (int) $nomorHari === $hariTerpilih;
            $daftarJadwal = $jadwal[$nomorHari] ?? [];
        @endphp
        <section
            id="{{ $idPrefix }}-hari-panel-{{ $nomorHari }}"
            role="tabpanel"
            aria-labelledby="{{ $idPrefix }}-hari-tab-{{ $nomorHari }}"
            tabindex="0"
            data-hari-panel="{{ $nomorHari }}"
            @if (! $aktif) hidden @endif
        >
            @if (empty($daftarJadwal))
                <div class="card py-8 text-center text-sm text-mute">
                    Tidak ada jadwal pelajaran pada hari {{ strtolower($namaHari) }}.
                </div>
            @else
                <div class="card divide-y divide-line p-0">
                    @foreach ($daftarJadwal as $item)
                        <article class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:gap-5">
                            <div class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-flame">
                                <x-icon name="clock" size="size-4" />
                                <span>{{ $item['mulai'] }} - {{ $item['selesai'] }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h2 class="truncate text-base font-bold text-ink">{{ $item['mapel'] }}</h2>
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-mute">
                                    <span>{{ $detailLabel }} <b class="font-semibold text-ink">{{ $item[$detailKey] }}</b></span>
                                    @if (!empty($item['ruang']) && $item['ruang'] !== '-')
                                        <span>Ruang <b class="font-medium text-ink">{{ $item['ruang'] }}</b></span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach
</div>