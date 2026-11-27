{{-- Pintasan: kartu kecil berisi ikon, judul, dan keterangan. <x-tile :href="route('tu.siswa.index')" judul="Data Induk" ket="Cari siswa" ikon="users" warna="blue" /> --}}
@props(['href', 'judul', 'ket' => null, 'ikon' => 'dot', 'warna' => 'blue', 'motion' => false])
@php
    $tile = [
        'blue'  => 'bg-flame/10 text-flame', 'sun' => 'bg-sun/15 text-sun-dark', 'leaf' => 'bg-leaf/12 text-leaf',
        'coral' => 'bg-coral/12 text-coral', 'grape' => 'bg-grape/12 text-grape',
    ][$warna] ?? 'bg-flame/10 text-flame';
@endphp
<a href="{{ $href }}" @if ($motion) data-motion-card @endif class="card card-hover {{ $motion ? '' : 'rise' }} group flex items-center gap-4 !p-4">
    <div class="grid size-12 shrink-0 place-items-center rounded-2xl {{ $tile }} transition group-hover:scale-110">
        <x-icon :name="$ikon" size="w-6 h-6" />
    </div>
    <div class="min-w-0 flex-1">
        <div class="truncate font-semibold text-navy">{{ $judul }}</div>
        @if ($ket)<div class="truncate text-xs text-mute">{{ $ket }}</div>@endif
    </div>
</a>