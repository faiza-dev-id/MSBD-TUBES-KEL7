{{-- Kartu angka. Cukup <x-stat angka="18" label="Menunggu diperiksa" />: ikon dan warna dipilih otomatis dari label.
     Bisa diatur sendiri: ikon="clock" warna="sun" ket="naik 3 dari kemarin". Warna: blue, sun, leaf, coral, grape. --}}
@props(['angka', 'label', 'ikon' => null, 'warna' => null, 'ket' => null])
@php
    $tones = [
        'blue'  => ['bg-flame/10 text-flame',    'from-sky to-flame',      'bg-flame/10'],
        'sun'   => ['bg-sun/15 text-sun-dark',   'from-sun to-amber-300',  'bg-sun/15'],
        'leaf'  => ['bg-leaf/12 text-leaf',      'from-leaf to-emerald-400', 'bg-leaf/10'],
        'coral' => ['bg-coral/12 text-coral',    'from-coral to-orange-400', 'bg-coral/10'],
        'grape' => ['bg-grape/12 text-grape',    'from-grape to-violet-400', 'bg-grape/10'],
    ];
    $warna ??= array_keys($tones)[crc32($label) % count($tones)];
    [$tile, $grad, $glow] = $tones[$warna] ?? $tones['blue'];

    $peta = ['siswa' => 'users', 'pendaftar' => 'idcard', 'disahkan' => 'check', 'verifikasi' => 'clock', 'menunggu' => 'clock',
             'perbaiki' => 'edit', 'pengajuan' => 'file', 'kelas' => 'home', 'kelengkapan' => 'trend', 'lengkap' => 'trend',
             'status' => 'shield', 'guru' => 'cap'];
    if (! $ikon) {
        $ikon = 'chart';
        foreach ($peta as $kata => $nama) {
            if (str_contains(mb_strtolower($label), $kata)) { $ikon = $nama; break; }
        }
    }
@endphp
<div class="card card-hover rise relative overflow-hidden !p-4 sm:!p-5">
    <div class="absolute -right-6 -top-6 size-24 rounded-full {{ $glow }}"></div>
    <div class="relative flex items-center gap-3 sm:gap-4">
        <div class="grid size-11 sm:size-12 shrink-0 place-items-center rounded-2xl {{ $tile }}">
            <x-icon :name="$ikon" size="w-6 h-6" />
        </div>
        <div class="min-w-0">
            <div class="text-2xl sm:text-3xl font-bold text-navy leading-none">{{ $angka }}</div>
            <div class="mt-1.5 text-xs sm:text-sm text-mute">{{ $label }}</div>
            @if ($ket)<div class="mt-1 text-xs font-semibold text-leaf">{{ $ket }}</div>@endif
        </div>
    </div>
    <div class="absolute inset-x-0 bottom-0 h-1 bg-linear-to-r {{ $grad }}"></div>
</div>