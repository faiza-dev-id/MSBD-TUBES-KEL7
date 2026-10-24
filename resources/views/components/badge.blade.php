@props(['status'])
@php
    $map = [
        'menunggu'     => ['Menunggu', 'bg-warn/15 text-warn'],
        'dikembalikan' => ['Dikembalikan', 'bg-bad/15 text-bad'],
        'ditolak'      => ['Ditolak', 'bg-bad/15 text-bad'],
        'disahkan'     => ['Disahkan', 'bg-ok/15 text-ok'],
        'disetujui'    => ['Disetujui', 'bg-ok/15 text-ok'],
    ];
    [$label, $cls] = $map[$status] ?? [ucfirst($status), 'bg-line text-mute'];
@endphp
<span {{ $attributes->merge(['class' => "inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold $cls"]) }}>{{ $label }}</span>