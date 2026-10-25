@props(['status'])
@php
    $map = [
        'menunggu'     => ['Menunggu',     'bg-sun/15 text-sun-dark', 'bg-sun'],
        'dikembalikan' => ['Dikembalikan', 'bg-coral/12 text-coral',  'bg-coral'],
        'ditolak'      => ['Ditolak',      'bg-bad/12 text-bad',      'bg-bad'],
        'disahkan'     => ['Disahkan',     'bg-leaf/12 text-leaf',    'bg-leaf'],
        'disetujui'    => ['Disetujui',    'bg-leaf/12 text-leaf',    'bg-leaf'],
        'aktif'        => ['Aktif',        'bg-leaf/12 text-leaf',    'bg-leaf'],
        'dibuka'       => ['Dibuka',       'bg-leaf/12 text-leaf',    'bg-leaf'],
        'ditutup'      => ['Ditutup',      'bg-line text-mute',       'bg-mute'],
    ];
    [$label, $cls, $dot] = $map[mb_strtolower($status)] ?? [ucfirst($status), 'bg-line text-mute', 'bg-mute'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold $cls"]) }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>{{ $label }}
</span>