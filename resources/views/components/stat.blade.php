@props(['angka', 'label'])
<div class="card flex items-center gap-4">
    <div class="w-1.5 self-stretch rounded-full bg-linear-to-b from-sky to-flame"></div>
    <div>
        <div class="text-3xl font-bold text-navy leading-none">{{ $angka }}</div>
        <div class="text-sm text-mute mt-2">{{ $label }}</div>
    </div>
</div>