@props(['angka', 'label'])
<div class="card flex items-center gap-3 sm:gap-4 !p-4 sm:!p-5">
    <div class="w-1.5 self-stretch rounded-full bg-linear-to-b from-sky to-flame"></div>
    <div class="min-w-0">
        <div class="text-2xl sm:text-3xl font-bold text-navy leading-none">{{ $angka }}</div>
        <div class="text-xs sm:text-sm text-mute mt-2">{{ $label }}</div>
    </div>
</div>