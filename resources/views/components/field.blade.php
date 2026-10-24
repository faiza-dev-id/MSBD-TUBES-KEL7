@props(['label', 'name', 'type' => 'text', 'hint' => null, 'placeholder' => ''])
<div>
    <label class="label" for="{{ $name }}">{{ $label }}</label>
    <input class="input" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ old($name) }}">
    @if ($hint) <p class="text-[11px] text-mute mt-1">{{ $hint }}</p> @endif
</div>