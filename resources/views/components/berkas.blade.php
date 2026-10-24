@props(['label', 'name', 'wajib' => false, 'hint' => null])
<div>
    <label class="flex items-center justify-between gap-3 border-2 border-dashed border-line rounded-xl p-3.5 text-sm cursor-pointer hover:border-flame">
        <span class="min-w-0">
            <span class="block font-medium">{{ $label }}@if ($wajib) <span class="text-bad">*</span>@endif</span>
            @if ($hint) <span class="block text-[11px] text-mute">{{ $hint }}</span> @endif
            <span class="nama-file block text-xs text-mute truncate">Belum ada file</span>
        </span>
        <span class="btn btn-ghost !py-1.5 shrink-0">Pilih file</span>
        <input type="file" name="{{ $name }}" class="hidden" accept=".pdf,.jpg,.jpeg,.png" data-nama="{{ $label }}" @if ($wajib) data-wajib @endif>
    </label>
</div>